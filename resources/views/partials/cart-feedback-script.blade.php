<style>
    .cart-toast {
        position: fixed;
        inset-inline: 1rem;
        bottom: 1rem;
        z-index: 10000;
        margin-inline: auto;
        max-width: 24rem;
        border-radius: 0.875rem;
        background: #070b1f;
        color: #ffffff;
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
        font-weight: 700;
        text-align: center;
        box-shadow: 0 24px 55px -28px rgba(2, 6, 23, 0.75);
        opacity: 0;
        transform: translateY(0.75rem) scale(0.98);
        transition: opacity 180ms ease, transform 180ms ease;
        pointer-events: none;
    }

    .cart-toast.is-visible {
        opacity: 1;
        transform: translateY(0) scale(1);
    }

    .cart-button-added {
        background-color: #15803d !important;
        color: #ffffff !important;
        transform: translateY(-1px);
    }

    .cart-badge-bump {
        animation: cartBadgeBump 560ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    @keyframes cartBadgeBump {
        0% { transform: scale(1); }
        35% { transform: scale(1.45); }
        70% { transform: scale(0.9); }
        100% { transform: scale(1); }
    }

    @media (prefers-reduced-motion: reduce) {
        .cart-toast { transition: none; }
        .cart-badge-bump { animation: none; }
    }
</style>

