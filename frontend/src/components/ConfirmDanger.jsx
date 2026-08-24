/**
 * ConfirmDanger.jsx — Pannello di conferma per azioni distruttive.
 *
 * Nato per "Abbandona Razza" (ScegliRazza.jsx), riusato identico da
 * "Abbandona Mestiere" (ScegliMestiere.jsx) e "Abbandona Gilda"
 * (MiaGilda.jsx): stesso stile per lo stesso tipo di azione ovunque compaia,
 * niente markup duplicato. Le conseguenze (children, di solito <li>) restano
 * a carico del chiamante — cambiano da caso a caso.
 *
 * Stili: base/_confirm_panel.scss (classi .danger-panel*, globali).
 *
 * @author Crystal Tokyo Dev
 */

export default function ConfirmDanger({ titolo, children, onConfirm, onCancel, busy, confermaLabel = 'Confermo' }) {
    return (
        <div className="danger-panel">
            <div className="danger-panel-title">
                <i className="fas fa-exclamation-triangle" />
                {titolo}
            </div>
            {children && <ul className="danger-panel-list">{children}</ul>}
            <div className="danger-panel-actions">
                <button type="button" className="btn btn--danger" onClick={onConfirm} disabled={busy}>
                    <i className="fas fa-check" /> {busy ? 'Attendere…' : confermaLabel}
                </button>
                <button type="button" className="btn btn--ghost" onClick={onCancel} disabled={busy}>
                    <i className="fas fa-times" /> Annulla
                </button>
            </div>
        </div>
    )
}
