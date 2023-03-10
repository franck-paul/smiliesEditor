$(() => {
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

  $('#smilies-list').sortable({
    cursor: 'move',
  });
  $('#smilies-list tr')
    .on('mouseenter', function () {
      $(this).css({
        cursor: 'move',
      });
    })
    .on('mouseleave', function () {
      $(this).css({
        cursor: 'auto',
      });
    });
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
});
