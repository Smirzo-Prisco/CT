const ns_parametri = {
	api_file: 'pages/api_personaggio.php',
	param: 'op'
};

const panel = document.getElementById('stats_panel');
const attributesContainer = document.getElementById('attributesContainer');
const xpDisponibiliEl = document.getElementById('xpDisponibili');
const shinDisponibiliEl = document.getElementById('shinDisponibili');
const totalBarXP = document.getElementById('totalBarXP');
const totalBarShin = document.getElementById('totalBarShin');
const levelTotalLabel = document.getElementById('levelTotalLabel');
const characterLevelEl = document.getElementById('characterLevel');
const baseStats = 50;

/* state */
let attributes = []; // each: {name, onlyShin, xp, shin, baseXp, baseShin}
let xpDisponibili = 0;
let shinDisponibili = 0;
let characterLevel = 0;
let totalXPsum = 0;
let totalShinsum = 0;
let soglie = [];

/* helper: create DOM structures without innerHTML */
function createAttributeCard(attr) {
	const card = document.createElement('div');
	card.className = 'attr-card';
	card.dataset.name = attr.name;

	// header
	const header = document.createElement('div');
	header.className = 'attr-header';

	const nameEl = document.createElement('div');
	nameEl.className = 'attr-name';
	nameEl.textContent = attr.name;
	header.appendChild(nameEl);

	if (attr.onlyShin) {
		const badge = document.createElement('div');
		badge.className = 'badge';
		badge.textContent = 'Solo Shin';
		header.appendChild(badge);
	}
	card.appendChild(header);

	// bar container
	const barContainer = document.createElement('div');
	barContainer.className = 'attr-bar-container';

	const barXP = document.createElement('div');
	barXP.className = 'attr-bar-xp';

	const barShin = document.createElement('div');
	barShin.className = 'attr-bar-shin';

	const barLabel = document.createElement('div');
	barLabel.className = 'attr-bar-label';
	barLabel.textContent = String(attr.xp + attr.shin);
	barContainer.appendChild(barXP);
	barContainer.appendChild(barShin);
	barContainer.appendChild(barLabel);
	card.appendChild(barContainer);

	// controls
	const controls = document.createElement('div');
	controls.className = 'controls';

	// select
	const lbl = document.createElement('label');
	lbl.textContent = 'Usa: ';

	const sel = document.createElement('select');
	sel.className = 'point-type';
	sel.title = 'Seleziona riserva punti';

	const optXp = document.createElement('option');
	optXp.value = 'xp';
	optXp.textContent = 'Esperienza';

	const optShin = document.createElement('option');
	optShin.value = 'shin';
	optShin.textContent = 'Shin';
	sel.appendChild(optXp);
	sel.appendChild(optShin);

	if (attr.onlyShin) {
		sel.value = 'shin';
		sel.disabled = true;
	}

	lbl.appendChild(sel);
	controls.appendChild(lbl);

	// buttons group
	const btns = document.createElement('div');
	btns.className = 'buttons';

	const createBtn = (cls, text) => {
		const b = document.createElement('button');
		b.className = cls;
		b.type = 'button';
		b.textContent = text;

		return b;
	};

	const minus = createBtn('minus', '-1');
	const plus = createBtn('plus', '+1');
	const minus10 = createBtn('minus10', '-10');
	const plus10 = createBtn('plus10', '+10');

	btns.appendChild(minus);
	btns.appendChild(plus);
	btns.appendChild(minus10);
	btns.appendChild(plus10);
	controls.appendChild(btns);
	card.appendChild(controls);

	// attach card
	attributesContainer.appendChild(card);
}

/* update UI from state */
function updateUI() {
	// RESET TOTALE
	totalXPsum = 0;
	totalShinsum = 0;

	const cards = attributesContainer.querySelectorAll('.attr-card');

	cards.forEach(card => {
		const name = card.dataset.name;
		const attr = attributes.find(a => a.name === name);
		const xpBar = card.querySelector('.attr-bar-xp');
		const shinBar = card.querySelector('.attr-bar-shin');
		const label = card.querySelector('.attr-bar-label');
		const sum = attr.xp + attr.shin;

		if (sum > 0) {
			xpBar.style.width = (attr.xp / sum * 100) + '%';
			shinBar.style.width = (attr.shin / sum * 100) + '%';
		} else {
			xpBar.style.width = '0%';
			shinBar.style.width = '0%';
		}
		label.textContent = String(sum);

		// aggiornamento totale corretto
		totalXPsum += attr.xp;
		totalShinsum += attr.shin;
	});

	const grandTotal = totalXPsum + totalShinsum;

	if (grandTotal > 0) {
		totalBarXP.style.width = (totalXPsum / grandTotal * 100) + '%';
		totalBarShin.style.width = (totalShinsum / grandTotal * 100) + '%';
	} else {
		totalBarXP.style.width = '0%';
		totalBarShin.style.width = '0%';
	}

	levelTotalLabel.textContent = `${grandTotal} punti`;
	characterLevelEl.textContent = getLevelPg(grandTotal, soglie);

	xpDisponibiliEl.textContent = xpDisponibili;
	shinDisponibiliEl.textContent = shinDisponibili;
}

const MENTE_NAME = 'Mente';
const TEMPRA_NAME = 'Tempra';

/* totale attuale (xp + shin) di un attributo per nome */
function getAttrTotal(name) {
	const a = attributes.find(x => x.name === name);

	return a ? (a.xp + a.shin) : 0;
}

/* totale che avrebbe l'attributo dopo il change, prima di applicarlo */
function computeCandidateTotal(attr, type, change) {
	if (change > 0) return attr.xp + attr.shin + change;

	const base = (type === 'xp') ? (attr.baseXp || 0) : (attr.baseShin || 0);
	const current = (type === 'xp') ? attr.xp : attr.shin;
	const availableToRemove = Math.max(0, current - base);
	const dec = Math.min(availableToRemove, -change);

	return attr.xp + attr.shin - dec;
}

/* Tempra non può mai superare il doppio del totale di Mente */
function wouldBreakTempraCap(attr, type, change) {
	if (attr.name !== MENTE_NAME && attr.name !== TEMPRA_NAME) return false;

	const candidateTotal = computeCandidateTotal(attr, type, change);

	if (attr.name === TEMPRA_NAME) return candidateTotal > getAttrTotal(MENTE_NAME) * 2;

	return getAttrTotal(TEMPRA_NAME) > candidateTotal * 2;
}

/* compute change with base limits */
function applyChangeToAttr(attr, type, change) {
	// change >0 add, change <0 remove
	if (change > 0) {
		// add only if pool has enough
		if (type === 'xp') {
			if (xpDisponibili >= change) {
				attr.xp += change;
				xpDisponibili -= change;

				return true;
			}
			return false;
		} else {
			if (shinDisponibili >= change) {
				attr.shin += change;
				shinDisponibili -= change;

				return true;
			}
			return false;
		}
	} else {
		// remove but not below base
		const base = (type === 'xp') ? (attr.baseXp || 0) : (attr.baseShin || 0);
		const current = (type === 'xp') ? attr.xp : attr.shin;
		const availableToRemove = current - base;

		if (availableToRemove <= 0) return false;

		const dec = Math.min(availableToRemove, -change);

		if (type === 'xp') {
			attr.xp -= dec;
			xpDisponibili += dec;
		} else {
			attr.shin -= dec;
			shinDisponibili += dec;
		}

		return true;
	}
}

/* event delegation for controls */
attributesContainer.addEventListener('click', (ev) => {
	const btn = ev.target.closest('button');

	if (!btn) return;

	const card = btn.closest('.attr-card');

	if (!card) return;

	const name = card.dataset.name;
	const attr = attributes.find(a => a.name === name);
	const sel = card.querySelector('.point-type');
	const type = sel ? sel.value : 'xp';
	const cls = btn.className;

	let change = 0;

	if (cls.includes('plus10')) change = 10;
	else if (cls.includes('plus')) change = 1;
	else if (cls.includes('minus10')) change = -10;
	else if (cls.includes('minus')) change = -1;

	if (change === 0) return;

	if (wouldBreakTempraCap(attr, type, change)) {
		alert('Tempra non può superare il doppio del totale di Mente.');
		return;
	}

	const ok = applyChangeToAttr(attr, type, change);

	if (!ok) {
		// nice, user-friendly messages
		if (change > 0) alert('Non hai abbastanza punti disponibili per questa operazione.');
		else alert('Non puoi rimuovere punti sotto il valore già salvato.');

		return;
	}

	updateUI();
});

/* fetch initial data */
function loadInitial() {
	// show loading placeholders
	attributesContainer.innerHTML = '';
	const loader = document.createElement('div');
	loader.textContent = 'Caricamento attributi...';
	loader.style.color = 'var(--muted)';
	attributesContainer.appendChild(loader);

	fetch(ns_parametri.api_file + '?' + ns_parametri.param + '=getPuntiPg', { credentials: 'same-origin' })
		.then(res => {
			if (!res.ok) throw new Error('HTTP ' + res.status);

			return res.json();
		})
		.then(data => {
			xpDisponibili = Number(data.xpDisponibili) || 0;
			shinDisponibili = Number(data.shinDisponibili) || 0;
			characterLevel = Number(data.level) || 0;
			totalXPsum = Number(data.xpAssegnati) || 0;
			totalShinsum = Number(data.shinAssegnati) || 0;
			soglie = data.soglie;
			attributes = (data.attributes || []).map(a => ({
				field: a.field,
				name: a.name,
				onlyShin: Boolean(a.onlyShin),
				xp: Number(a.xp) || 0,
				shin: Number(a.shin) || 0,
				baseXp: Number(a.xp) || 0,
				baseShin: Number(a.shin) || 0
			}));

			// build DOM
			attributesContainer.innerHTML = '';
			attributes.forEach(createAttributeCard);

			// Consulta la tabella (tabella soglie)
			const table_soglie = createSoglieTable(soglie);
			document.getElementById('tooltip_soglie').appendChild(table_soglie);

			updateUI();
		})
		.catch(err => {
			attributesContainer.innerHTML = '';
			const errEl = document.createElement('div');
			errEl.style.color = 'salmon';
			errEl.textContent = 'Errore caricamento attributi: ' + err.message;
			attributesContainer.appendChild(errEl);
			xpDisponibili = 0;
			shinDisponibili = 0;
			xpDisponibiliEl.textContent = String(xpDisponibili);
			shinDisponibiliEl.textContent = String(shinDisponibili);
		});
}

// Calcolo il livello del pg
function getLevelPg(totStats, soglie) {
	let level = 1;

	for (const row of soglie) {
		if (totStats <= Number(row.soglia)) return Math.max(1, Number(row.livello));

		level = Number(row.livello);
	}

	return level;
}

/* save handler */
function savePuntiPg(saveBtn) {
	var canSave = false;

	// Verifico se ho incrementato i punti
	attributes.forEach(a => {
		if ((a.xp - a.baseXp) > 0 || (a.shin - a.baseShin) > 0) canSave = true;
	});

	if (canSave) {
		saveBtn.disabled = true;
		saveBtn.textContent = 'Salvataggio...';

		fetch(ns_parametri.api_file + '?' + ns_parametri.param + '=savePuntiPg', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			credentials: 'same-origin',
			body: JSON.stringify({
				xpDisponibili,
				shinDisponibili,
				attributes: attributes.map(a => ({
					field: a.field,
					name: a.name,
					xp: a.xp,
					shin: a.shin
				}))
			})
		})
			.then(res => {
				if (!res.ok) throw new Error('HTTP ' + res.status);

				return res.json();
			})
			.then(resp => {
				// assume success -> update baseXp/baseShin to current values
				attributes.forEach(a => {
					a.baseXp = a.xp;
					a.baseShin = a.shin;
				});

				showNotification('Modifiche salvate correttamente!', 'success');
			})
			.catch(err => {
				showNotification('Errore durante il salvataggio:' + err.message, 'error');
			})
			.finally(() => {
				saveBtn.disabled = false;
				saveBtn.textContent = '💾 Salva modifiche';

				updateUI();
			});
	} else showNotification('Non hai incrementato nessun punteggio', 'error');
}

function createSoglieTable(soglie) {
	// container
	const table = document.createElement('table');
	table.className = 'soglie-table';

	// header
	const thead = document.createElement('thead');
	const trh = document.createElement('tr');
	const th1 = document.createElement('th');

	th1.textContent = 'Livello';

	const th2 = document.createElement('th');

	th2.textContent = 'Soglia';
	trh.appendChild(th1);
	trh.appendChild(th2);
	thead.appendChild(trh);
	table.appendChild(thead);

	// body
	const tbody = document.createElement('tbody');

	soglie.forEach(row => {
		const tr = document.createElement('tr');
		const tdLivello = document.createElement('td');

		tdLivello.textContent = row.livello;

		const tdSoglia = document.createElement('td');

		tdSoglia.textContent = row.soglia;

		tr.appendChild(tdLivello);
		tr.appendChild(tdSoglia);

		tbody.appendChild(tr);
	});

	table.appendChild(tbody);

	return table;
}

/* initialize */
loadInitial();