/* Formatting helpers shared by the UI pages (ported from ica-guest data.js/shell.js). */

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

export const fmtDate = (e) => {
    const d = new Date(e);
    return `${d.getDate()} ${MONTHS[d.getMonth()]} ${d.getFullYear()}`;
};
export const fmtDay = (e) => DAYS[new Date(e).getDay()];
export const dayNum = (e) => new Date(e).getDate();
export const monName = (e) => MONTHS[new Date(e).getMonth()].toUpperCase();
/* "1 Nov, 2020" — the console Home page's sermon-card date format. */
export const fmtDateComma = (e) => {
    const d = new Date(e);
    return `${d.getDate()} ${MONTHS[d.getMonth()]}, ${d.getFullYear()}`;
};
/* "Sunday, September 27" — the console Home page's prayer-point date format. */
export const fmtLongDay = (e) => new Date(e).toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });

/* "Rev. Dr. Enson M. Lwesya": suffix is the honorific that precedes the name. */
export const authorName = (a) => [a?.suffix, a?.name].filter(Boolean).join(' ');

const escapeHtml = (str) =>
    str.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
/* Sermon body as HTML: sample data is an array of plain paragraphs, the API sends (purified) HTML. */
export const bodyHtml = (body) => (Array.isArray(body) ? body.map((p) => `<p>${escapeHtml(p)}</p>`).join('') : body || '');

export const initials = (name) =>
    (name || '')
        .replace(/^(Rev\.|Dr\.|Ps\.|Mr|Mrs|Ms)\s*/gi, '')
        .trim()
        .split(/\s+/)
        .map((w) => w[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();

/* Avatar tint class (a-1 … a-6) derived from a numeric id. */
export const avClass = (id) => 'a-' + (((+id || 0) % 6) + 1);

const TONES = [
    'linear-gradient(140deg,#2384c9,#14568f)', 'linear-gradient(140deg,#35a3a3,#217373)',
    'linear-gradient(140deg,#7183da,#4f5fb8)', 'linear-gradient(140deg,#9a6cc8,#6f43a6)',
    'linear-gradient(140deg,#587a99,#34495f)', 'linear-gradient(140deg,#d76c78,#b23246)',
];
/* Stable gradient for a monogram tile, derived from its title. */
export const tone = (str) => {
    let h = 0;
    for (const c of str || '') h = (h + c.charCodeAt(0)) % TONES.length;
    return TONES[h];
};
export const mono = (title) => (title || '?').trim()[0].toUpperCase();

/* Copies text; resolves true on success. navigator.clipboard only exists on secure
   origins (https / localhost), so fall back to a hidden textarea + execCommand. */
export const copyText = async (text) => {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
        }
    } catch (e) {}
    try {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.cssText = 'position:fixed;top:0;left:0;opacity:0';
        document.body.appendChild(ta);
        ta.select();
        const ok = document.execCommand('copy');
        ta.remove();
        return ok;
    } catch (e) {
        return false;
    }
};

/* Breadcrumbs for the pages under "More" in the rail: Home › More › <label>. */
export const moreCrumbs = (label) => [{ label: 'More', href: route('ui.events') }, { label }];

/* Pill sub-nav shared by the pages under "More" in the rail. */
export const moreTabs = (active) =>
    [
        { id: 'events', label: 'Events' },
        { id: 'attendance', label: 'Attendance' },
        { id: 'prayer', label: 'Prayer Points' },
        { id: 'resources', label: 'Resources' },
        { id: 'about', label: 'About' },
    ].map((t) => ({ label: t.label, href: route(`ui.${t.id}`), on: t.id === active }));
