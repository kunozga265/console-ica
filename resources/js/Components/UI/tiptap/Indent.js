import { Extension } from '@tiptap/core';

/*
 * Keeps `data-indent` on paragraphs, headings and lists. Notes written in the
 * mobile app (Quill) can indent a paragraph, or start a list already indented;
 * HTML has no equivalent, so the server's converter (App\Support\QuillDelta)
 * marks them with data-indent and this keeps the mark through web edits.
 */
export const Indent = Extension.create({
    name: 'indent',

    addGlobalAttributes() {
        return [
            {
                types: ['paragraph', 'heading', 'bulletList', 'orderedList'],
                attributes: {
                    indent: {
                        default: 0,
                        parseHTML: (el) => parseInt(el.getAttribute('data-indent') || '0', 10) || 0,
                        renderHTML: (attrs) => (attrs.indent ? { 'data-indent': attrs.indent, style: `margin-left: ${attrs.indent * 1.5}em` } : {}),
                    },
                },
            },
        ];
    },
});

export default Indent;
