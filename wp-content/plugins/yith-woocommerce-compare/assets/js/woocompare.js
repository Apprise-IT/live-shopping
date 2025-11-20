/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
var __webpack_exports__ = {};

;// ./node_modules/@babel/runtime/helpers/esm/typeof.js
function _typeof(o) {
  "@babel/helpers - typeof";

  return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) {
    return typeof o;
  } : function (o) {
    return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
  }, _typeof(o);
}

;// ./node_modules/@babel/runtime/helpers/esm/toPrimitive.js

function toPrimitive(t, r) {
  if ("object" != _typeof(t) || !t) return t;
  var e = t[Symbol.toPrimitive];
  if (void 0 !== e) {
    var i = e.call(t, r || "default");
    if ("object" != _typeof(i)) return i;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return ("string" === r ? String : Number)(t);
}

;// ./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js


function toPropertyKey(t) {
  var i = toPrimitive(t, "string");
  return "symbol" == _typeof(i) ? i : i + "";
}

;// ./node_modules/@babel/runtime/helpers/esm/defineProperty.js

function _defineProperty(e, r, t) {
  return (r = toPropertyKey(r)) in e ? Object.defineProperty(e, r, {
    value: t,
    enumerable: !0,
    configurable: !0,
    writable: !0
  }) : e[r] = t, e;
}

;// ./node_modules/@babel/runtime/helpers/esm/classCallCheck.js
function _classCallCheck(a, n) {
  if (!(a instanceof n)) throw new TypeError("Cannot call a class as a function");
}

;// ./node_modules/@babel/runtime/helpers/esm/createClass.js

function _defineProperties(e, r) {
  for (var t = 0; t < r.length; t++) {
    var o = r[t];
    o.enumerable = o.enumerable || !1, o.configurable = !0, "value" in o && (o.writable = !0), Object.defineProperty(e, toPropertyKey(o.key), o);
  }
}
function _createClass(e, r, t) {
  return r && _defineProperties(e.prototype, r), t && _defineProperties(e, t), Object.defineProperty(e, "prototype", {
    writable: !1
  }), e;
}

;// ./node_modules/@babel/runtime/helpers/esm/arrayWithHoles.js
function _arrayWithHoles(r) {
  if (Array.isArray(r)) return r;
}

;// ./node_modules/@babel/runtime/helpers/esm/iterableToArrayLimit.js
function _iterableToArrayLimit(r, l) {
  var t = null == r ? null : "undefined" != typeof Symbol && r[Symbol.iterator] || r["@@iterator"];
  if (null != t) {
    var e,
      n,
      i,
      u,
      a = [],
      f = !0,
      o = !1;
    try {
      if (i = (t = t.call(r)).next, 0 === l) {
        if (Object(t) !== t) return;
        f = !1;
      } else for (; !(f = (e = i.call(t)).done) && (a.push(e.value), a.length !== l); f = !0);
    } catch (r) {
      o = !0, n = r;
    } finally {
      try {
        if (!f && null != t["return"] && (u = t["return"](), Object(u) !== u)) return;
      } finally {
        if (o) throw n;
      }
    }
    return a;
  }
}

;// ./node_modules/@babel/runtime/helpers/esm/arrayLikeToArray.js
function _arrayLikeToArray(r, a) {
  (null == a || a > r.length) && (a = r.length);
  for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e];
  return n;
}

;// ./node_modules/@babel/runtime/helpers/esm/unsupportedIterableToArray.js

function _unsupportedIterableToArray(r, a) {
  if (r) {
    if ("string" == typeof r) return _arrayLikeToArray(r, a);
    var t = {}.toString.call(r).slice(8, -1);
    return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0;
  }
}

;// ./node_modules/@babel/runtime/helpers/esm/nonIterableRest.js
function _nonIterableRest() {
  throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.");
}

;// ./node_modules/@babel/runtime/helpers/esm/slicedToArray.js




function slicedToArray_slicedToArray(r, e) {
  return _arrayWithHoles(r) || _iterableToArrayLimit(r, e) || _unsupportedIterableToArray(r, e) || _nonIterableRest();
}

;// ./assets/js/src/includes/common.js
/* global jQuery */




var addQueryArgs = function addQueryArgs(args, url) {
    var urlObj = new URL(url || window.location.href),
      searchParams = urlObj.searchParams;
    Object.entries(args).map(function (_ref) {
      var _ref2 = _slicedToArray(_ref, 2),
        key = _ref2[0],
        value = _ref2[1];
      return searchParams.set(key, value);
    });
    return urlObj.toString();
  },
  block = function block($item) {
    if ('undefined' === typeof jQuery.fn.block) {
      return;
    }
    $item.addClass('js-blocked').block({
      message: null,
      overlayCSS: {
        background: '#fff url(' + yith_woocompare.loader + ') no-repeat center',
        backgroundSize: '20px 20px',
        opacity: 0.6
      }
    });
  },
  unblock = function unblock($item) {
    if ('undefined' === typeof jQuery.fn.unblock) {
      return;
    }
    $item.removeClass('js-blocked').unblock();
  },
  getCookie = function getCookie(cookieName) {
    var cookies = document.cookie.split(';').reduce(function (a, i) {
      var _i$trim$split = i.trim().split('='),
        _i$trim$split2 = slicedToArray_slicedToArray(_i$trim$split, 2),
        key = _i$trim$split2[0],
        value = _i$trim$split2[1];
      a[key] = value;
      return a;
    }, {});
    return cookies === null || cookies === void 0 ? void 0 : cookies[cookieName];
  };

;// ./assets/js/src/includes/globals.js


var $ = jQuery,
  $document = $(document),
  $body = $(document.body),
  $window = $(window);

;// ./assets/js/src/includes/YITH_WooCompare.js



function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { _defineProperty(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }


var YITH_WooCompare = /*#__PURE__*/function () {
  function YITH_WooCompare() {
    _classCallCheck(this, YITH_WooCompare);
    if ($body.hasClass('elementor-editor-active')) {
      return;
    }
    this.refresh();
    this.init();
  }
  return _createClass(YITH_WooCompare, [{
    key: "init",
    value: function init() {
      this.initTables();
      this.initEvents();
      this.initWidget();
    }
  }, {
    key: "initEvents",
    value: function initEvents() {
      var _this = this;
      // add to cart.
      $body.on('added_to_cart', this.onAddedToCart);

      // reload / refresh.
      $window.on('resize orientationchange', function () {
        return _this.initTables();
      });
      $document.on('yith_woocompare_refresh_table yith_woocompare_table_updated', function () {
        return _this.initTables();
      });
      $document.on('yith_woocompare_table_updated', function () {
        return _this.initSlider();
      });

      // add.
      $document.on('click', 'a.compare:not(.added)', function (ev) {
        return ev.preventDefault(), _this.add($(ev.target));
      });

      // remove.
      $document.on('click', '.compare-list .remove a, a.yith_woocompare_clear', function (ev) {
        return ev.preventDefault(), _this.remove($(ev.target));
      });
      $document.on('click', 'a.compare.added input[type="checkbox"]', function (ev) {
        return ev.preventDefault(), _this.remove($(ev.target)), false;
      });

      // open popup.
      $document.on('yith_woocompare_open_popup', function (ev) {
        return ev.preventDefault(), _this.openPopup();
      });
      $document.on('click', 'a.compare.added', function (ev) {
        return ev.preventDefault(), _this.openPopup();
      });
      $document.on('click', '.yith-woocompare-open a, a.yith-woocompare-open', function (ev) {
        return ev.preventDefault(), _this.openPopup();
      });

      // filter.
      $document.on('click', '#yith-woocompare-cat-nav li > a', function (ev) {
        return ev.preventDefault(), _this.filter($(ev.target));
      });
    }
  }, {
    key: "getTables",
    value: function getTables() {
      var includeInitialized = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
      var $tables = $document.find('#yith-woocompare table.compare-list');
      if (!includeInitialized) {
        $tables = $tables.not('.dataTable');
      }
      return $tables;
    }
  }, {
    key: "initTables",
    value: function initTables() {
      var _this2 = this;
      if ('undefined' === typeof $.fn.DataTable || 'undefined' === typeof $.fn.imagesLoaded) {
        return;
      }
      var $tables = this.getTables();
      if (!$tables.length) {
        return;
      }
      $tables.get().map(function (table) {
        return _this2.initTable($(table));
      });
    }
  }, {
    key: "initTable",
    value: function initTable($table) {
      $table.DataTable().destroy();
      $table.imagesLoaded(function () {
        $table.DataTable({
          'info': false,
          'scrollX': true,
          'scrollCollapse': true,
          'paging': false,
          'ordering': false,
          'searching': false,
          'autoWidth': false,
          'destroy': true,
          'fixedColumns': {
            leftColumns: yith_woocompare.fixedcolumns
          },
          'columnDefs': [{
            width: 250,
            targets: 0
          }]
        });
      });
    }
  }, {
    key: "initSlider",
    value: function initSlider() {
      if ('undefined' === typeof $.fn.owlCarousel) {
        return;
      }
      var related = $('#yith-woocompare-related'),
        slider = related.find('.related-products'),
        nav = related.find('.related-slider-nav');
      if (!related.length) {
        return;
      }
      slider.owlCarousel({
        autoplay: yith_woocompare.autoplay_related,
        autoplayHoverPause: true,
        loop: true,
        margin: 15,
        responsiveClass: true,
        responsive: {
          0: {
            items: 2
          },
          // breakpoint from 480 up
          480: {
            items: 3
          },
          // breakpoint from 768 up
          768: {
            items: yith_woocompare.num_related
          }
        }
      });
      if (nav.length) {
        nav.find('.related-slider-nav-prev').click(function () {
          slider.trigger('prev.owl.carousel');
        });
        nav.find('.related-slider-nav-next').click(function () {
          slider.trigger('next.owl.carousel');
        });
      }
    }
  }, {
    key: "initWidget",
    value: function initWidget() {
      var _this3 = this;
      $document.on('click', '.yith-woocompare-widget a.compare-widget', function (ev) {
        return ev.preventDefault(), _this3.openPopup();
      }).on('click', '.yith-woocompare-widget li a.remove, .yith-woocompare-widget a.clear-all', function (ev) {
        return ev.preventDefault(), _this3.remove($(ev.target));
      });
    }
  }, {
    key: "add",
    value: function add($initiator) {
      var _this4 = this;
      $initiator = $initiator.closest('[data-product_id]');
      var _yith_woocompare = yith_woocompare,
        addedLabel = _yith_woocompare.added_label,
        autoOpen = _yith_woocompare.auto_open,
        isPage = _yith_woocompare.is_page,
        productId = $initiator.data('product_id'),
        isRelated = $initiator.closest('.yith-woocompare-related').length;
      return this.doAjax($initiator, 'add', {
        id: productId
      }).success(function (response) {
        var onlyOne = response.only_one,
          tableUrl = response.table_url,
          limitReached = response.limit_reached,
          added = response.added;
        if (added && !isRelated) {
          $initiator.addClass('added').attr('href', tableUrl).find('input[type="checkbox"]').prop('checked', true).change().end().find('.label').html(addedLabel);
          if (autoOpen && !onlyOne && !isPage) {
            $document.trigger('yith_woocompare_open_popup', {
              response: tableUrl,
              button: $initiator
            });
          }
        }
        limitReached && $('.compare:not(.added)').addClass('disabled');
        _this4.replaceFragments(response);
        _this4.refreshCounter();
        added && $document.trigger('yith_woocompare_product_added', {
          productId: productId,
          $initiator: $initiator
        });
      });
    }
  }, {
    key: "remove",
    value: function remove($initiator) {
      var _this5 = this;
      $initiator = $initiator.closest('[data-product_id]');
      var productId = $initiator.data('product_id');
      return this.doAjax($initiator, 'remove', {
        id: productId
      }).success(function (response) {
        var _yith_woocompare2 = yith_woocompare,
          customLabel = _yith_woocompare2.custom_label_for_compare_button,
          customSelector = _yith_woocompare2.selector_for_custom_label_compare_button,
          defaultText = _yith_woocompare2.button_text,
          limitReached = response.limit_reached,
          removed = response.removed,
          toRemove = 'all' === productId ? '.compare.added' : ".compare[data-product_id=\"".concat(productId, "\"]"),
          buttonText = customLabel ? $initiator.closest('tbody').find("tr ".concat(customSelector)).find("td.product_".concat(productId)).text() : defaultText;
        removed && $(toRemove, window.parent.document).removeClass('added').find('input[type="checkbox"]').prop('checked', false).change().end().find('.label').html(buttonText);
        !limitReached && $('.compare:not(.added)').removeClass('disabled');
        _this5.replaceFragments(response);
        _this5.refreshCounter();
        removed && $document.trigger('yith_woocompare_product_removed', {
          productId: productId,
          $initiator: $initiator
        });
      });
    }
  }, {
    key: "filter",
    value: function filter($initiator) {
      var _this6 = this;
      $initiator = $initiator.closest('[data-cat_id]');
      var $nav = $initiator.closest('#yith-woocompare-cat-nav > ul'),
        cat = $initiator.data('cat_id'),
        products = $nav.data('product_ids');
      return this.doAjax($initiator, 'filter', {
        yith_compare_cat: cat,
        yith_compare_prod: products
      }).success(function (response) {
        _this6.replaceFragments(response);
        _this6.refreshCounter();
      });
    }
  }, {
    key: "maybeShowPreviewBar",
    value: function maybeShowPreviewBar() {
      var $previewBar = $('#yith-woocompare-preview-bar');
      if (!$previewBar.hasClass('shown')) {
        return;
      }
      $previewBar.show();
    }
  }, {
    key: "hidePreviewBar",
    value: function hidePreviewBar() {
      $('#yith-woocompare-preview-bar').hide();
    }
  }, {
    key: "openPopup",
    value: function openPopup() {
      var _this7 = this;
      var _yith_woocompare3 = yith_woocompare,
        forcePopup = _yith_woocompare3.force_showing_popup,
        pageUrl = _yith_woocompare3.page_url,
        isPage = _yith_woocompare3.is_page;
      if (isPage || !forcePopup && $window.width < 768) {
        window.location = pageUrl;
        return;
      }
      var $container = $('.yith-woocompare-popup-container');
      $container = $container.length ? $container : this.buildPopupContainer();
      $('html, body').css('overflow', 'hidden');
      $body.addClass('yith-woocompare-popup-open');
      $container.show();
      this.hidePreviewBar();
      this.refreshFragments().then(function () {
        return _this7.hidePreviewBar();
      });
    }
  }, {
    key: "closePopup",
    value: function closePopup() {
      var $container = $('.yith-woocompare-popup-container');
      if (!$container.length) {
        return;
      }
      $container.hide();
      $('html, body').css('overflow', 'auto');
      $body.removeClass('yith-woocompare-popup-open');
      this.maybeShowPreviewBar();
    }
  }, {
    key: "buildPopupContainer",
    value: function buildPopupContainer() {
      var _this8 = this;
      var $closeButton = $('<a/>', {
          'href': '#',
          'role': 'button',
          'html': '&times;',
          'class': 'yith-woocompare-popup-close'
        }),
        $container = $('<div/>', {
          'class': 'yith-woocompare-popup-container'
        }),
        $tableWrapper = $('<div/>', {
          'class': 'yith-woocompare-table-wrapper'
        }),
        $tableScrollWrapper = $('<div/>', {
          'class': 'yith-woocompare-table-scroll-wrapper'
        }),
        $tablePlaceholder = $('<div/>', {
          'id': 'yith-woocompare',
          'class': 'yith-woocompare-table-placeholder'
        });
      $tableScrollWrapper.prepend($closeButton).append($tablePlaceholder).appendTo($tableWrapper);
      $container.append($tableWrapper).appendTo('body');
      $closeButton.on('click', function () {
        return _this8.closePopup();
      });
      return $container;
    }
  }, {
    key: "redirectToPage",
    value: function redirectToPage() {}
  }, {
    key: "doAjax",
    value: function doAjax($initiator, key, data) {
      var _yith_woocompare4, _yith_woocompare5;
      var action = (_yith_woocompare4 = yith_woocompare) === null || _yith_woocompare4 === void 0 || (_yith_woocompare4 = _yith_woocompare4.actions) === null || _yith_woocompare4 === void 0 ? void 0 : _yith_woocompare4[key],
        security = (_yith_woocompare5 = yith_woocompare) === null || _yith_woocompare5 === void 0 || (_yith_woocompare5 = _yith_woocompare5.nonces) === null || _yith_woocompare5 === void 0 ? void 0 : _yith_woocompare5[key];
      return $.ajax({
        type: 'post',
        url: this.getAjxUrl(action),
        data: _objectSpread({
          action: action,
          security: security,
          context: 'frontend',
          lang: $initiator.data('lang')
        }, data || {}),
        dataType: 'json',
        cache: false,
        beforeSend: function beforeSend() {
          return block($initiator);
        },
        complete: function complete() {
          return unblock($initiator);
        },
        error: function error() {
          for (var _len = arguments.length, errorParams = new Array(_len), _key = 0; _key < _len; _key++) {
            errorParams[_key] = arguments[_key];
          }
          return console.log(errorParams);
        }
      });
    }
  }, {
    key: "getAjxUrl",
    value: function getAjxUrl(action) {
      return yith_woocompare.ajaxurl.toString().replace('%%endpoint%%', action);
    }
  }, {
    key: "refresh",
    value: function refresh() {
      this.refreshFragments();
      this.refreshCounter();
    }
  }, {
    key: "refreshFragments",
    value: function refreshFragments() {
      var _this9 = this;
      var $fragments = $('.yith-woocompare-widget-content').add('#yith-woocompare:not(.fixed-compare-table)').add('#yith-woocompare-preview-bar');
      if (!$fragments.length) {
        return;
      }
      return this.doAjax($fragments, 'reload').success(function (response) {
        return _this9.replaceFragments(response);
      });
    }
  }, {
    key: "refreshCounter",
    value: function refreshCounter() {
      var $counter = $('.yith-woocompare-counter');
      if (!$counter.length) {
        return;
      }
      var _yith_woocompare6 = yith_woocompare,
        cookieName = _yith_woocompare6.cookie_name,
        type = $counter.data('type'),
        text = $counter.data('text_o'),
        cookie = getCookie(cookieName);
      var c;
      try {
        c = cookie ? JSON.parse(cookie).length : 0;
      } catch (e) {
        return;
      }
      $counter.find('.yith-woocompare-count').html('text' === type ? text.replace('{{count}}', c) : c);
      $document.trigger('yith_woocompare_counter_updated', c);
    }
  }, {
    key: "replaceFragments",
    value: function replaceFragments(response) {
      var tableHtml = response.table_html,
        widgetHtml = response.widget_html,
        barHtml = response.preview_bar_html;
      if (tableHtml) {
        $('#yith-woocompare:not(.fixed-compare-table)').replaceWith(tableHtml);
        $document.trigger('yith_woocompare_table_updated');
      }
      if (widgetHtml) {
        $('.yith-woocompare-widget-content').replaceWith(widgetHtml);
        $document.trigger('yith_woocompare_widget_updated');
      }
      if (barHtml) {
        $('#yith-woocompare-preview-bar').replaceWith(barHtml);
      }
      $document.trigger('yith_woocompare_fragments_replaced', response);
    }
  }, {
    key: "onAddedToCart",
    value: function onAddedToCart(ev, fragments, cart_hash, button) {
      var $button = $(button);
      if ($button.closest('table.compare-list').length) {
        $button.hide();
      }
    }
  }]);
}();

;// ./assets/js/src/frontend.js




(function () {
  return $window.yithCompare = new YITH_WooCompare();
})();
var __webpack_export_target__ = window;
for(var __webpack_i__ in __webpack_exports__) __webpack_export_target__[__webpack_i__] = __webpack_exports__[__webpack_i__];
if(__webpack_exports__.__esModule) Object.defineProperty(__webpack_export_target__, "__esModule", { value: true });
/******/ })()
;
//# sourceMappingURL=woocompare.js.map