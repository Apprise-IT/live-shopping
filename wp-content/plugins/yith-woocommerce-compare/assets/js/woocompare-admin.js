/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
jQuery(document).ready(function ($) {
  $(".attributes .fields").sortable({
    cursor: "move",
    scrollSensitivity: 10,
    tolerance: "pointer",
    axis: "y",
    items: 'li:not(.fixed)',
    stop: function stop(event, ui) {
      var list = ui.item.parents('.fields'),
        fields = new Array();
      $('input[type="checkbox"]', list).each(function (i) {
        fields[i] = $(this).val();
      });
      list.next().val(fields.join(','));
    }
  }).find('input').on('change', function () {
    var $check = $(this);
    $check.val($check.data('value'));
  }).change();

  // ############### PANEL OPTIONS ###################

  $('input[type="checkbox"]').on('woocompare_input_init change', function () {
    if (!$(this).is(':checked')) {
      $('[data-deps="' + this.id + '"]').parents('tr').fadeOut();
    } else {
      $('[data-deps="' + this.id + '"]').parents('tr').fadeIn();
    }
  }).trigger('woocompare_input_init');

  // ################ SHARE PANEL ####################

  // select2 to select socials
  $(".yith-woocompare-chosen").select2({
    placeholder: "Select social..."
  });

  // ##################### SHORTCODE PANEL ####################

  $('.yith-woocompare-comparison-tables').closest('.yith-plugin-fw__panel__content__page').on('click', '.edit, .add', function (ev) {
    ev.preventDefault();
    var $t = $(ev.target),
      $tr = $t.closest('tr'),
      data = $tr.data('item') || {
        id: 0,
        title: '',
        products: {}
      },
      args = {
        title: data.id ? yith_woocompare.labels.update_comparison_table_modal_title : yith_woocompare.labels.create_comparison_table_modal_title,
        content: wp.template('yith-woocompare-add-comparison-table-modal')(data),
        footer: false,
        showClose: true,
        width: 400
      };
    var modal = yith.ui.modal(args);

    // init enhanced selects
    $(document.body).trigger('wc-enhanced-select-init');
    var $productSelect = $(modal.elements.content).find('#product_ids');

    // init product select
    for (var i in data.products) {
      var _$productSelect$find;
      if (!((_$productSelect$find = $productSelect.find("[value=\"".concat(i, "\"]"))) !== null && _$productSelect$find !== void 0 && _$productSelect$find.length)) {
        $productSelect.append($('<option/>', {
          value: i,
          text: data.products[i],
          selected: true
        }));
      }
    }
    $productSelect.change();
  }).find('h1').after("<a class=\"button-primary add yith-add-button\">".concat(yith_woocompare.labels.add_comparison_table_button_label, "</a>"));
});
var __webpack_export_target__ = window;
for(var __webpack_i__ in __webpack_exports__) __webpack_export_target__[__webpack_i__] = __webpack_exports__[__webpack_i__];
if(__webpack_exports__.__esModule) Object.defineProperty(__webpack_export_target__, "__esModule", { value: true });
/******/ })()
;
//# sourceMappingURL=woocompare-admin.js.map