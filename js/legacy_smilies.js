/*global $, dotclear, jsToolBar */
'use strict';

$(() => {
  // Get buttons (id, code, icon)
  const buttons = dotclear.getData('smilieseditor');
  for (const button in buttons) {
    const name = `smilieseditor_s${buttons[button].id}`;
    jsToolBar.prototype.elements[name] = {
      type: 'button',
      title: buttons[button].code,
      icon: buttons[button].icon,
      fn: {
        wiki() {
          this.encloseSelection(buttons[button].code, '');
        },
        xhtml() {
          this.encloseSelection(buttons[button].code, '');
        },
        wysiwyg() {
          const smiley = document.createTextNode(buttons[button].code);
          this.insertNode(smiley);
        },
      },
    };
  }
});
