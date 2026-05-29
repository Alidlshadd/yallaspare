<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CategoriesExport;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\SecureImageStorage;
use App\Support\SqlSafe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $stockFilter = trim((string) $request->query('stock', ''));

        $query = Category::query()->withCount('products');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                SqlSafe::whereLike($q, 'name_en', $search);
                SqlSafe::orWhereLike($q, 'name_ar', $search);
                SqlSafe::orWhereLike($q, 'name_ku', $search);
                SqlSafe::orWhereLike($q, 'slug', $search);
                SqlSafe::orWhereLike($q, 'description', $search);
            });
        }

        if ($stockFilter === 'empty') {
            $query->doesntHave('products');
        } elseif ($stockFilter === 'has_products') {
            $query->has('products');
        }

        $allowedSorts = ['id', 'name_en', 'name_ar', 'name_ku', 'slug', 'products_count', 'created_at'];
        $sort = (string) $request->query('sort', 'id');
        $direction = strtolower((string) $request->query('dir', 'desc'));

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'desc';
        }

        $query->orderBy($sort, $direction);
        if ($sort !== 'id') {
            $query->orderBy('id', $direction);
        }

        $categories = $query
            ->paginate(12)
            ->withQueryString();

        $totalCategories = Category::count();
        $emptyCategories = Category::doesntHave('products')->count();

        return view('admin.categories.index', compact(
            'categories',
            'search',
            'stockFilter',
            'sort',
            'direction',
            'totalCategories',
            'emptyCategories'
        ));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_ku' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $baseSlug = Str::slug((string) ($data['slug'] ?: $data['name_en']));
        $data['slug'] = $this->makeUniqueSlug($baseSlug !== '' ? $baseSlug : 'category');
        if ($request->hasFile('image')) {
            $data['image'] = SecureImageStorage::store($request->file('image'), 'categories');
        }

        Category::create($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('Category created successfully.'));
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_ku' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);

        $baseSlug = Str::slug((string) ($data['slug'] ?: $data['name_en']));
        $data['slug'] = $this->makeUniqueSlug($baseSlug !== '' ? $baseSlug : 'category', $category->id);
        $data['image'] = $category->image;

        if ($request->boolean('remove_image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            $data['image'] = null;
        }

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            $data['image'] = SecureImageStorage::store($request->file('image'), 'categories');
        }

        $category->update($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('Category updated successfully.'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', __('Cannot delete category with assigned products.'));
        }

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', __('Category deleted successfully.'));
    }

    public function exportExcel()
    {
        try {
            return Excel::download(new CategoriesExport(), 'categories.xlsx');
        } catch (\Throwable $e) {
            Log::error('Categories Excel export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', __('Failed to export categories to Excel. Please try again.'));
        }
    }

    public function import(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), $this->importValidationRules());
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $parsed = $this->parseImportFile($request->file('import_file'));
            $header = $parsed['header'];
            $requiredColumns = ['name_en', 'name_ar', 'name_ku'];
            foreach ($requiredColumns as $column) {
                if (!in_array($column, $header, true)) {
                    return back()->with('error', __('Missing required column: :column', ['column' => $column]));
                }
            }

            $imported = 0;
            $errors = [];

            foreach ($parsed['rows'] as $entry) {
                $rowNumber = $entry['row'];
                $rowData = $entry['data'];
                $rowValidator = Validator::make($rowData, $this->importRowValidationRules());
                if ($rowValidator->fails()) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'name_en' => $rowData['name_en'] ?? '',
                        'message' => implode('; ', $rowValidator->errors()->all()),
                    ];
                    continue;
                }

                $baseSlug = Str::slug((string) (($rowData['slug'] ?? '') !== '' ? $rowData['slug'] : $rowData['name_en']));
                $slug = $this->makeUniqueSlug($baseSlug !== '' ? $baseSlug : 'category');

                $payload = [
                    'name_en' => (string) $rowData['name_en'],
                    'name_ar' => (string) $rowData['name_ar'],
                    'name_ku' => (string) $rowData['name_ku'],
                    'slug' => $slug,
                    'description' => ($rowData['description'] ?? '') !== '' ? (string) $rowData['description'] : null,
                ];

                try {
                    Category::create($payload);
                    $imported++;
                } catch (\Throwable $e) {
                    Log::error('Category import row failed', [
                        'row' => $rowNumber,
                        'name_en' => $payload['name_en'],
                        'error' => $e->getMessage(),
                    ]);

                    $errors[] = [
                        'row' => $rowNumber,
                        'name_en' => $payload['name_en'],
                        'message' => __('Could not save this row due to a database error.'),
                    ];
                }
            }

            $message = __('Import completed successfully. Total imported rows: :imported.', ['imported' => $imported]);
            if (!empty($errors)) {
                $message .= ' ' . __('Some rows were skipped. Please review the import errors.');
            }

            return redirect()
                ->route('admin.categories.index')
                ->with('success', $message)
                ->with('import_errors', $errors);
        } catch (\Throwable $e) {
            Log::error('Category import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', __('Import failed unexpectedly. Please verify the file format and try again.'));
        }
    }

    private function makeUniqueSlug(string $baseSlug, ?int $ignoreId = null): string
    {
        $slug = $baseSlug;
        $counter = 1;

        while (Category::query()
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function importValidationRules(): array
    {
        return [
            'import_file' => ['required', 'file', 'max:5120', 'mimes:csv,txt,xls,xlsx'],
        ];
    }

    private function importRowValidationRules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_ku' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }

    private function detectDelimiter(string $line): string
    {
        $delimiters = [',', ';', "\t"];
        $bestDelimiter = ',';
        $maxColumns = 0;

        foreach ($delimiters as $delimiter) {
            $columns = count(str_getcsv($line, $delimiter));
            if ($columns > $maxColumns) {
                $maxColumns = $columns;
                $bestDelimiter = $delimiter;
            }
        }

        return $bestDelimiter;
    }

    private function parseImportFile(\Illuminate\Http\UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        if ($path === false) {
            throw new \RuntimeException(__('Unable to read uploaded file.'));
        }

        return match ($extension) {
            'csv', 'txt' => $this->parseCsvFile($path),
            'xls', 'xlsx' => $this->parseExcelFile($path),
            default => throw new \RuntimeException(__('Unsupported file type. Please upload CSV or Excel (.xls/.xlsx).')),
        };
    }

    private function parseCsvFile(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new \RuntimeException(__('Unable to open uploaded file.'));
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                throw new \RuntimeException(__('Import file is empty.'));
            }

            $delimiter = $this->detectDelimiter($firstLine);
            rewind($handle);

            $rawHeader = fgetcsv($handle, 0, $delimiter);
            if (!$rawHeader) {
                throw new \RuntimeException(__('Import file is empty.'));
            }

            $header = array_map(fn ($h) => strtolower(trim((string) $h)), $rawHeader);
            $rows = [];
            $rowNumber = 1;

            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rowNumber++;
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $rows[] = [
                    'row' => $rowNumber,
                    'data' => $this->mapRowToHeader($header, $row),
                ];
            }

            return ['header' => $header, 'rows' => $rows];
        } finally {
            fclose($handle);
        }
    }

    private function parseExcelFile(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rawRows = $sheet->toArray(null, false, false, false);

        if (empty($rawRows)) {
            throw new \RuntimeException(__('Import file is empty.'));
        }

        $rawHeader = array_shift($rawRows);
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), (array) $rawHeader);

        if (count(array_filter($header, fn ($h) => $h !== '')) === 0) {
            throw new \RuntimeException(__('Import file is empty.'));
        }

        $rows = [];
        foreach ($rawRows as $index => $row) {
            if ($this->isEmptyRow((array) $row)) {
                continue;
            }

            $rows[] = [
                'row' => $index + 2,
                'data' => $this->mapRowToHeader($header, (array) $row),
            ];
        }

        return ['header' => $header, 'rows' => $rows];
    }

    private function mapRowToHeader(array $header, array $row): array
    {
        $rowData = [];
        foreach ($header as $index => $column) {
            if ($column === '') {
                continue;
            }

            $rowData[$column] = isset($row[$index]) ? trim((string) $row[$index]) : null;
        }

        return $rowData;
    }

    private function isEmptyRow(array $row): bool
    {
        return count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0;
    }
}
