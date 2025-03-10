/*global $, dotclear */
'use strict';

dotclear.ready(() => {
  const smilies = dotclear.getData('smilies');
  dotclear.smilies_base_url = smilies.smilies_base_url;
  dotclear.msg.confirm_image_delete = smilies.confirm_image_delete;

  $('.checkboxes-helpers').each(function () {
    dotclear.checkboxesHelpers(this);
  });

  $('#smilepic').on('change', function () {
    $('#smiley-preview')
      .attr('src', dotclear.smilies_base_url + this.value)
      .attr('title', this.value)
      .attr('alt', this.value);
  });

  $('#smilepic,select.emote,#smilepic option,select.emote option').each(function () {
    $(this)
      .css('background-image', `url(${dotclear.smilies_base_url}${this.value})`)
      .on('change', function () {
        $(this).css('background-image', `url(${dotclear.smilies_base_url}${this.value})`);
      });
  });

  $('#smilies-list').sortable();
  $('#saveorder').on('click', () => {
    const order = [];
    $('#smilies-list tr td input.position').each(function () {
      order.push(this.name.replace(/^order\[([^\]]+)\]$/, '$1'));
    });
    $('input[name=smilies_order]')[0].value = order.join(',');
    return true;
  });
  $('#smilies-list tr td input.position').hide();
  $('#smilies-list tr td.handle').addClass('handler');

  $('#del_form').on('submit', () => window.confirm(dotclear.msg.confirm_image_delete));
});
