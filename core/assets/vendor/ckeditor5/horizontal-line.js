/*!
 * @license Copyright (c) 2003-2021, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md.
 */
!function(e){const t=e.en=e.en||{};t.dictionary=Object.assign(t.dictionary||{},{"Horizontal line":"Horizontal line"})}(window.CKEDITOR_TRANSLATIONS||(window.CKEDITOR_TRANSLATIONS={})),window.CKEditor5=window.CKEditor5||{},window.CKEditor5.horizontalLine=function(e){var t={};function n(r){if(t[r])return t[r].exports;var o=t[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,n),o.l=!0,o.exports}return n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var o in e)n.d(r,o,function(t){return e[t]}.bind(null,o));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=7)}([function(e,t,n){e.exports=n(2)("./src/core.js")},function(e,t,n){e.exports=n(2)("./src/widget.js")},function(e,t){e.exports=CKEditor5.dll},function(e,t,n){e.exports=n(2)("./src/ui.js")},function(e,t,n){var r=n(5),o=n(6);"string"==typeof(o=o.__esModule?o.default:o)&&(o=[[e.i,o,""]]);var i={injectType:"singletonStyleTag",attributes:{"data-cke":!0},insert:"head",singleton:!0};r(o,i);e.exports=o.locals||{}},function(e,t,n){"use strict";var r,o=function(){return void 0===r&&(r=Boolean(window&&document&&document.all&&!window.atob)),r},i=function(){var e={};return function(t){if(void 0===e[t]){var n=document.querySelector(t);if(window.HTMLIFrameElement&&n instanceof window.HTMLIFrameElement)try{n=n.contentDocument.head}catch(e){n=null}e[t]=n}return e[t]}}(),a=[];function c(e){for(var t=-1,n=0;n<a.length;n++)if(a[n].identifier===e){t=n;break}return t}function s(e,t){for(var n={},r=[],o=0;o<e.length;o++){var i=e[o],s=t.base?i[0]+t.base:i[0],l=n[s]||0,u="".concat(s," ").concat(l);n[s]=l+1;var d=c(u),f={css:i[1],media:i[2],sourceMap:i[3]};-1!==d?(a[d].references++,a[d].updater(f)):a.push({identifier:u,updater:g(f,t),references:1}),r.push(u)}return r}function l(e){var t=document.createElement("style"),r=e.attributes||{};if(void 0===r.nonce){var o=n.nc;o&&(r.nonce=o)}if(Object.keys(r).forEach((function(e){t.setAttribute(e,r[e])})),"function"==typeof e.insert)e.insert(t);else{var a=i(e.insert||"head");if(!a)throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");a.appendChild(t)}return t}var u,d=(u=[],function(e,t){return u[e]=t,u.filter(Boolean).join("\n")});function f(e,t,n,r){var o=n?"":r.media?"@media ".concat(r.media," {").concat(r.css,"}"):r.css;if(e.styleSheet)e.styleSheet.cssText=d(t,o);else{var i=document.createTextNode(o),a=e.childNodes;a[t]&&e.removeChild(a[t]),a.length?e.insertBefore(i,a[t]):e.appendChild(i)}}function p(e,t,n){var r=n.css,o=n.media,i=n.sourceMap;if(o?e.setAttribute("media",o):e.removeAttribute("media"),i&&"undefined"!=typeof btoa&&(r+="\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(i))))," */")),e.styleSheet)e.styleSheet.cssText=r;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(r))}}var h=null,m=0;function g(e,t){var n,r,o;if(t.singleton){var i=m++;n=h||(h=l(t)),r=f.bind(null,n,i,!1),o=f.bind(null,n,i,!0)}else n=l(t),r=p.bind(null,n,t),o=function(){!function(e){if(null===e.parentNode)return!1;e.parentNode.removeChild(e)}(n)};return r(e),function(t){if(t){if(t.css===e.css&&t.media===e.media&&t.sourceMap===e.sourceMap)return;r(e=t)}else o()}}e.exports=function(e,t){(t=t||{}).singleton||"boolean"==typeof t.singleton||(t.singleton=o());var n=s(e=e||[],t);return function(e){if(e=e||[],"[object Array]"===Object.prototype.toString.call(e)){for(var r=0;r<n.length;r++){var o=c(n[r]);a[o].references--}for(var i=s(e,t),l=0;l<n.length;l++){var u=c(n[l]);0===a[u].references&&(a[u].updater(),a.splice(u,1))}n=i}}}},function(e,t){e.exports=".ck-editor__editable .ck-horizontal-line{display:flow-root}.ck-content hr{margin:15px 0;height:4px;background:#dedede;border:0}"},function(e,t,n){"use strict";n.r(t),n.d(t,"HorizontalLine",(function(){return l})),n.d(t,"HorizontalLineEditing",(function(){return a})),n.d(t,"HorizontalLineUI",(function(){return s}));var r=n(0),o=n(1);class i extends r.Command{refresh(){const e=this.editor.model,t=e.schema,n=e.document.selection;this.isEnabled=function(e,t,n){const r=function(e,t){const n=Object(o.findOptimalInsertionRange)(e,t).start.parent;if(n.isEmpty&&!n.is("element","$root"))return n.parent;return n}(e,n);return t.checkChild(r,"horizontalLine")}(n,t,e)}execute(){const e=this.editor.model;e.change(t=>{const n=t.createElement("horizontalLine");e.insertContent(n);let r=n.nextSibling;!(r&&e.schema.checkChild(r,"$text"))&&e.schema.checkChild(n.parent,"paragraph")&&(r=t.createElement("paragraph"),e.insertContent(r,t.createPositionAfter(n))),r&&t.setSelection(r,0)})}}n(4);class a extends r.Plugin{static get pluginName(){return"HorizontalLineEditing"}init(){const e=this.editor,t=e.model.schema,n=e.t,r=e.conversion;t.register("horizontalLine",{isObject:!0,allowWhere:"$block"}),r.for("dataDowncast").elementToElement({model:"horizontalLine",view:(e,{writer:t})=>t.createEmptyElement("hr")}),r.for("editingDowncast").elementToElement({model:"horizontalLine",view:(e,{writer:t})=>{const r=n("Horizontal line"),i=t.createContainerElement("div"),a=t.createEmptyElement("hr");return t.addClass("ck-horizontal-line",i),t.setCustomProperty("hr",!0,i),t.insert(t.createPositionAt(i,0),a),function(e,t,n){return t.setCustomProperty("horizontalLine",!0,e),Object(o.toWidget)(e,t,{label:n})}(i,t,r)}}),r.for("upcast").elementToElement({view:"hr",model:"horizontalLine"}),e.commands.add("horizontalLine",new i(e))}}var c=n(3);class s extends r.Plugin{static get pluginName(){return"HorizontalLineUI"}init(){const e=this.editor,t=e.t;e.ui.componentFactory.add("horizontalLine",n=>{const r=e.commands.get("horizontalLine"),o=new c.ButtonView(n);return o.set({label:t("Horizontal line"),icon:'<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M2 9h16v2H2z"/></svg>',tooltip:!0}),o.bind("isEnabled").to(r,"isEnabled"),this.listenTo(o,"execute",()=>{e.execute("horizontalLine"),e.editing.view.focus()}),o})}}class l extends r.Plugin{static get requires(){return[a,s,o.Widget]}static get pluginName(){return"HorizontalLine"}}}]);