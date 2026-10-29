import { Mark, mergeAttributes } from '@tiptap/core';

/*
 * Fill-in-the-blank words in a sermon: <mark data-fill="k3x9qa">answer</mark>.
 * A <mark> (not a <span>) because the server's sentence renderer rewrites every
 * <span> to number highlights. Each blank gets its own random id, which members'
 * saved answers are keyed by.
 */
const newId = () => Math.random().toString(36).slice(2, 8).padEnd(6, '0');

export const Fillable = Mark.create({
    name: 'fillable',
    inclusive: false,
    excludes: 'fillable link',

    addAttributes() {
        return {
            id: {
                default: null,
                parseHTML: (el) => el.getAttribute('data-fill'),
                renderHTML: (attrs) => ({ 'data-fill': attrs.id || newId() }),
            },
        };
    },

    parseHTML() {
        return [{ tag: 'mark[data-fill]' }];
    },

    renderHTML({ HTMLAttributes }) {
        return ['mark', mergeAttributes(HTMLAttributes, { class: 'fill' }), 0];
    },

    addCommands() {
        return {
            /** Returns an error message (string) when the selection can't become a blank. */
            toggleFillable:
                () =>
                ({ state, commands }) => {
                    if (this.editor.isActive(this.name)) {
                        return commands.extendMarkRange(this.name) && commands.unsetMark(this.name);
                    }
                    const { from, to, $from, $to, empty } = state.selection;
                    if (empty || !$from.sameParent($to)) return false;
                    const text = state.doc.textBetween(from, to);
                    // The server splits sentences on ". " — a blank must not straddle one.
                    if (!text.trim() || /[.;]\s/.test(text) || text.length > 80) return false;
                    return commands.setMark(this.name, { id: newId() });
                },
        };
    },
});

export default Fillable;
