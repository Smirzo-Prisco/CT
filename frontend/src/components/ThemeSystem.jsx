// ============================================================
// ThemeSystem.jsx — Ponte tra i token SCSS e i componenti React
//
// Tutti i valori fanno riferimento alle CSS custom properties
// definite in _tokens.scss (:root). Nessun colore/dimensione
// è hardcoded qui: modificare _tokens.scss per cambiare look.
// ============================================================

import { createContext, useContext } from "react";

/* =========================
   1. DESIGN TOKENS (JS bridge)
   Mappa i token SCSS → variabili JS consumabili nei componenti.
   I valori sono var(--xxx) che il browser risolve da :root.
========================= */

export const theme = {
    colors: {
        bg: "var(--color-bg)",            // $palette-bg
        surface: "var(--color-surface)",       // $palette-surface
        textPrimary: "var(--color-text)",          // $palette-text
        textSecondary: "var(--color-text-secondary)",// $palette-text-secondary
        accentGold: "var(--color-text-title)",    // $palette-text-title — titoli/dettagli
        accentIce: "var(--color-action)",        // $palette-action — link/azioni
        accentIceHover: "var(--color-action-hover)",  // $palette-action-hover
        muted: "var(--color-text-muted)",    // $palette-text-muted
        danger: "var(--color-danger)",
        success: "var(--color-success)",
    },
    font: {
        base: "var(--font-base)",   // Verdana — testo di gioco
        chat: "var(--font-chat)",   // Inter — UI moderna
        serif: "var(--font-base)",  // DejaVu Serif — testo narrativo
        ui: "var(--font-ui)",     // Lato — controlli
    },
    text: {
        xs: "var(--text-xs)",
        sm: "var(--text-sm)",
        md: "var(--text-md)",
        lg: "var(--text-lg)",
        xl: "var(--text-xl)",
        "2xl": "var(--text-2xl)",
        "3xl": "var(--text-3xl)",
    },
    space: {
        1: "var(--space-1)",
        2: "var(--space-2)",
        3: "var(--space-3)",
        4: "var(--space-4)",
        5: "var(--space-5)",
        6: "var(--space-6)",
        8: "var(--space-8)",
    },
    radius: {
        sm: "var(--radius-sm)",
        md: "var(--radius-md)",
        lg: "var(--radius-lg)",
        xl: "var(--radius-xl)",
        full: "var(--radius-full)",
    },
    shadow: {
        sm: "var(--shadow-sm)",
        md: "var(--shadow-md)",
        lg: "var(--shadow-lg)",
    },
    transition: {
        fast: "var(--transition-fast)",
        normal: "var(--transition-normal)",
    },
};

/* =========================
   2. THEME CONTEXT
========================= */

const ThemeContext = createContext(theme);

export const ThemeProvider = ({ children }) => (
    <ThemeContext.Provider value={theme}>
        <div style={{
            background: theme.colors.bg,
            color: theme.colors.textPrimary,
            fontFamily: theme.font.chat,
            minHeight: "100vh",
        }}>
            {children}
        </div>
    </ThemeContext.Provider>
);

export const useTheme = () => useContext(ThemeContext);

/* =========================
   3. CHAT UI COMPONENTS
========================= */

export const ChatWindow = ({ children }) => {
    const { colors, space, radius } = useTheme();

    return (
        <div style={{
            background: colors.surface,
            // rgba di $palette-action (AEE7FF) con opacità minima
            border: "1px solid rgba(174, 231, 255, 0.05)",
            borderRadius: radius.xl,
            padding: space[4],
            maxWidth: 720,
            margin: "40px auto",
            display: "flex",
            flexDirection: "column",
            gap: space[3],
        }}>
            {children}
        </div>
    );
};

export const ChatMessage = ({ role = "user", children }) => {
    const { colors, text, radius } = useTheme();
    const isUser = role === "user";

    return (
        <div style={{
            alignSelf: isUser ? "flex-end" : "flex-start",
            // rgba di $palette-action con opacità bassa per messaggi utente
            background: isUser ? "rgba(174, 231, 255, 0.08)" : "transparent",
            // bordo sinistro $palette-text-title per messaggi di sistema/NPC
            borderLeft: !isUser ? `2px solid ${colors.accentGold}` : "none",
            padding: "8px 12px",
            borderRadius: radius.lg,
            maxWidth: "80%",
            color: colors.textPrimary,
            fontSize: text.lg,
            lineHeight: 1.6,
        }}>
            {children}
        </div>
    );
};

export const ChatInput = () => {
    const { colors, space, radius, text, font } = useTheme();

    return (
        <div style={{
            marginTop: space[3],
            display: "flex",
            gap: space[2],
        }}>
            <input
                placeholder="Scrivi la tua azione..."
                style={{
                    flex: 1,
                    background: colors.bg,
                    // rgba di $palette-action con opacità minima per il bordo
                    border: "1px solid rgba(174, 231, 255, 0.08)",
                    borderRadius: radius.lg,
                    padding: "10px 12px",
                    color: colors.textPrimary,
                    fontFamily: font.base,
                    fontSize: text.md,
                    outline: "none",
                }}
            />
            <button
                style={{
                    background: "transparent",
                    border: `1px solid ${colors.accentIce}`,
                    color: colors.accentIce,
                    padding: "10px 14px",
                    borderRadius: radius.lg,
                    fontFamily: font.base,
                    fontSize: text.md,
                    cursor: "pointer",
                    transition: "color var(--transition-fast), border-color var(--transition-fast)",
                }}
                onMouseEnter={e => {
                    e.currentTarget.style.color = colors.accentIceHover;
                    e.currentTarget.style.borderColor = colors.accentIceHover;
                }}
                onMouseLeave={e => {
                    e.currentTarget.style.color = colors.accentIce;
                    e.currentTarget.style.borderColor = colors.accentIce;
                }}
            >
                Invia
            </button>
        </div>
    );
};

/* =========================
   4. DEMO (rimuovere in produzione)
========================= */

export default function ThemeDemo() {
    return (
        <ThemeProvider>
            <ChatWindow>
                <ChatMessage role="system">
                    Benvenuto nel Crystal Tokyo Interface.
                </ChatMessage>
                <ChatMessage role="user">
                    Attivo azione: entro nel palazzo di cristallo.
                </ChatMessage>
                <ChatMessage role="system">
                    L'aria vibra di luce fredda azzurra.
                </ChatMessage>
                <ChatInput />
            </ChatWindow>
        </ThemeProvider>
    );
}
