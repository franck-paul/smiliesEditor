/*global $ */
'use strict';

dotclear.ready(() => {
  const target = $('#c_content').parent();
  $('p.smilies').insertBefore(target);
});

function InsertSmiley(textarea, smiley) {
  smiley = ` ${smiley} `;
  textarea = document.getElementById(textarea);
  textarea.focus();
  let start;
  let end;
  let scrollPos;
  if (typeof document.selection != 'undefined') {
    document.selection.createRange().text = smiley;
    textarea.caretPos += smiley.length;
  } else if (typeof textarea.setSelectionRange != 'undefined') {
    start = textarea.selectionStart;
    end = textarea.selectionEnd;
    scrollPos = textarea.scrollTop;
    textarea.value = textarea.value.substring(0, start) + smiley + textarea.value.substring(end);
    textarea.setSelectionRange(start + smiley.length, start + smiley.length);
    textarea.scrollTop = scrollPos;
  }
}
