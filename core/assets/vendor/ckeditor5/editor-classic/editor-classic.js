/*!
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md.
 */(()=>{var e={655:(e,t,o)=>{"use strict";o.d(t,{Z:()=>n});var r=o(609),i=o.n(r)()((function(e){return e[1]}));i.push([e.id,".ck.ck-editor{position:relative}.ck.ck-editor .ck-editor__top .ck-sticky-panel .ck-toolbar{z-index:var(--ck-z-modal)}.ck.ck-editor__top .ck-sticky-panel .ck-toolbar{border-radius:0}.ck-rounded-corners .ck.ck-editor__top .ck-sticky-panel .ck-toolbar,.ck.ck-editor__top .ck-sticky-panel .ck-toolbar.ck-rounded-corners{border-radius:var(--ck-border-radius);border-bottom-left-radius:0;border-bottom-right-radius:0}.ck.ck-editor__top .ck-sticky-panel .ck-toolbar{border-bottom-width:0}.ck.ck-editor__top .ck-sticky-panel .ck-sticky-panel__content_sticky .ck-toolbar{border-bottom-width:1px;border-radius:0}.ck-rounded-corners .ck.ck-editor__top .ck-sticky-panel .ck-sticky-panel__content_sticky .ck-toolbar,.ck.ck-editor__top .ck-sticky-panel .ck-sticky-panel__content_sticky .ck-toolbar.ck-rounded-corners{border-radius:var(--ck-border-radius);border-radius:0}.ck.ck-editor__main>.ck-editor__editable{background:var(--ck-color-base-background);border-radius:0}.ck-rounded-corners .ck.ck-editor__main>.ck-editor__editable,.ck.ck-editor__main>.ck-editor__editable.ck-rounded-corners{border-radius:var(--ck-border-radius);border-top-left-radius:0;border-top-right-radius:0}.ck.ck-editor__main>.ck-editor__editable:not(.ck-focused){border-color:var(--ck-color-base-border)}",""]);const n=i},609:e=>{"use strict";e.exports=function(e){var t=[];return t.toString=function(){return this.map((function(t){var o=e(t);return t[2]?"@media ".concat(t[2]," {").concat(o,"}"):o})).join("")},t.i=function(e,o,r){"string"==typeof e&&(e=[[null,e,""]]);var i={};if(r)for(var n=0;n<this.length;n++){var c=this[n][0];null!=c&&(i[c]=!0)}for(var a=0;a<e.length;a++){var s=[].concat(e[a]);r&&i[s[0]]||(o&&(s[2]?s[2]="".concat(o," and ").concat(s[2]):s[2]=o),t.push(s))}},t}},62:(e,t,o)=>{"use strict";var r,i=function(){return void 0===r&&(r=Boolean(window&&document&&document.all&&!window.atob)),r},n=function(){var e={};return function(t){if(void 0===e[t]){var o=document.querySelector(t);if(window.HTMLIFrameElement&&o instanceof window.HTMLIFrameElement)try{o=o.contentDocument.head}catch(e){o=null}e[t]=o}return e[t]}}(),c=[];function a(e){for(var t=-1,o=0;o<c.length;o++)if(c[o].identifier===e){t=o;break}return t}function s(e,t){for(var o={},r=[],i=0;i<e.length;i++){var n=e[i],s=t.base?n[0]+t.base:n[0],l=o[s]||0,d="".concat(s," ").concat(l);o[s]=l+1;var u=a(d),h={css:n[1],media:n[2],sourceMap:n[3]};-1!==u?(c[u].references++,c[u].updater(h)):c.push({identifier:d,updater:k(h,t),references:1}),r.push(d)}return r}function l(e){var t=document.createElement("style"),r=e.attributes||{};if(void 0===r.nonce){var i=o.nc;i&&(r.nonce=i)}if(Object.keys(r).forEach((function(e){t.setAttribute(e,r[e])})),"function"==typeof e.insert)e.insert(t);else{var c=n(e.insert||"head");if(!c)throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");c.appendChild(t)}return t}var d,u=(d=[],function(e,t){return d[e]=t,d.filter(Boolean).join("\n")});function h(e,t,o,r){var i=o?"":r.media?"@media ".concat(r.media," {").concat(r.css,"}"):r.css;if(e.styleSheet)e.styleSheet.cssText=u(t,i);else{var n=document.createTextNode(i),c=e.childNodes;c[t]&&e.removeChild(c[t]),c.length?e.insertBefore(n,c[t]):e.appendChild(n)}}function p(e,t,o){var r=o.css,i=o.media,n=o.sourceMap;if(i?e.setAttribute("media",i):e.removeAttribute("media"),n&&"undefined"!=typeof btoa&&(r+="\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(n))))," */")),e.styleSheet)e.styleSheet.cssText=r;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(r))}}var f=null,b=0;function k(e,t){var o,r,i;if(t.singleton){var n=b++;o=f||(f=l(t)),r=h.bind(null,o,n,!1),i=h.bind(null,o,n,!0)}else o=l(t),r=p.bind(null,o,t),i=function(){!function(e){if(null===e.parentNode)return!1;e.parentNode.removeChild(e)}(o)};return r(e),function(t){if(t){if(t.css===e.css&&t.media===e.media&&t.sourceMap===e.sourceMap)return;r(e=t)}else i()}}e.exports=function(e,t){(t=t||{}).singleton||"boolean"==typeof t.singleton||(t.singleton=i());var o=s(e=e||[],t);return function(e){if(e=e||[],"[object Array]"===Object.prototype.toString.call(e)){for(var r=0;r<o.length;r++){var i=a(o[r]);c[i].references--}for(var n=s(e,t),l=0;l<o.length;l++){var d=a(o[l]);0===c[d].references&&(c[d].updater(),c.splice(d,1))}o=n}}}},704:(e,t,o)=>{e.exports=o(79)("./src/core.js")},492:(e,t,o)=>{e.exports=o(79)("./src/engine.js")},273:(e,t,o)=>{e.exports=o(79)("./src/ui.js")},209:(e,t,o)=>{e.exports=o(79)("./src/utils.js")},434:(e,t,o)=>{e.exports=o(79)("./src/watchdog.js")},79:e=>{"use strict";e.exports=CKEditor5.dll}},t={};function o(r){var i=t[r];if(void 0!==i)return i.exports;var n=t[r]={id:r,exports:{}};return e[r](n,n.exports,o),n.exports}o.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return o.d(t,{a:t}),t},o.d=(e,t)=>{for(var r in t)o.o(t,r)&&!o.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},o.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),o.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},o.nc=void 0;var r={};(()=>{"use strict";o.r(r),o.d(r,{ClassicEditor:()=>D});var e=o(273),t=o(492),i=o(209);class n extends e.EditorUI{constructor(t,o){super(t),this.view=o,this._toolbarConfig=(0,e.normalizeToolbarConfig)(t.config.get("toolbar")),this._elementReplacer=new i.ElementReplacer,this.listenTo(t.editing.view,"scrollToTheSelection",this._handleScrollToTheSelectionWithStickyPanel.bind(this))}get element(){return this.view.element}init(e){const t=this.editor,o=this.view,r=t.editing.view,i=o.editable,n=r.document.getRoot();i.name=n.rootName,o.render();const c=i.element;this.setEditableElement(i.name,c),o.editable.bind("isFocused").to(this.focusTracker),r.attachDomRoot(c),e&&this._elementReplacer.replace(e,this.element),this._initPlaceholder(),this._initToolbar(),this.fire("ready")}destroy(){super.destroy();const e=this.view,t=this.editor.editing.view;this._elementReplacer.restore(),t.detachDomRoot(e.editable.name),e.destroy()}_initToolbar(){const e=this.view;e.stickyPanel.bind("isActive").to(this.focusTracker,"isFocused"),e.stickyPanel.limiterElement=e.element,e.stickyPanel.bind("viewportTopOffset").to(this,"viewportOffset",(({top:e})=>e||0)),e.toolbar.fillFromConfig(this._toolbarConfig,this.componentFactory),this.addToolbar(e.toolbar)}_initPlaceholder(){const e=this.editor,o=e.editing.view,r=o.document.getRoot(),i=e.sourceElement;let n;const c=e.config.get("placeholder");c&&(n="string"==typeof c?c:c[this.view.editable.name]),!n&&i&&"textarea"===i.tagName.toLowerCase()&&(n=i.getAttribute("placeholder")),n&&(0,t.enablePlaceholder)({view:o,element:r,text:n,isDirectHost:!1,keepOnFocus:!0})}_handleScrollToTheSelectionWithStickyPanel(e,t,o){const r=this.view.stickyPanel;if(r.isSticky){const e=new i.Rect(r.element).height;t.viewportOffset.top+=e}else{const e=()=>{this.editor.editing.view.scrollToTheSelection(o)};this.listenTo(r,"change:isSticky",e),setTimeout((()=>{this.stopListening(r,"change:isSticky",e)}),20)}}}var c=o(62),a=o.n(c),s=o(655),l={injectType:"singletonStyleTag",attributes:{"data-cke":!0},insert:"head",singleton:!0};a()(s.Z,l);s.Z.locals;class d extends e.BoxedEditorUIView{constructor(t,o,r={}){super(t),this.stickyPanel=new e.StickyPanelView(t),this.toolbar=new e.ToolbarView(t,{shouldGroupWhenFull:r.shouldToolbarGroupWhenFull}),this.editable=new e.InlineEditableUIView(t,o)}render(){super.render(),this.stickyPanel.content.add(this.toolbar),this.top.add(this.stickyPanel),this.main.add(this.editable)}}var u=o(704),h=o(434);const p=function(e){return null!=e&&"object"==typeof e};const f="object"==typeof global&&global&&global.Object===Object&&global;var b="object"==typeof self&&self&&self.Object===Object&&self;const k=(f||b||Function("return this")()).Symbol;var v=Object.prototype,m=v.hasOwnProperty,g=v.toString,y=k?k.toStringTag:void 0;const _=function(e){var t=m.call(e,y),o=e[y];try{e[y]=void 0;var r=!0}catch(e){}var i=g.call(e);return r&&(t?e[y]=o:delete e[y]),i};var w=Object.prototype.toString;const T=function(e){return w.call(e)};var j=k?k.toStringTag:void 0;const S=function(e){return null==e?void 0===e?"[object Undefined]":"[object Null]":j&&j in Object(e)?_(e):T(e)};const x=function(e,t){return function(o){return e(t(o))}}(Object.getPrototypeOf,Object);var E=Function.prototype,O=Object.prototype,C=E.toString,P=O.hasOwnProperty,F=C.call(Object);const M=function(e){if(!p(e)||"[object Object]"!=S(e))return!1;var t=x(e);if(null===t)return!0;var o=P.call(t,"constructor")&&t.constructor;return"function"==typeof o&&o instanceof o&&C.call(o)==F};const R=function(e){return p(e)&&1===e.nodeType&&!M(e)};class D extends((0,u.DataApiMixin)((0,u.ElementApiMixin)(u.Editor))){constructor(e,t={}){if(!N(e)&&void 0!==t.initialData)throw new i.CKEditorError("editor-create-initial-data",null);super(t),void 0===this.config.get("initialData")&&this.config.set("initialData",function(e){return N(e)?(0,i.getDataFromElement)(e):e}(e)),N(e)&&(this.sourceElement=e),this.model.document.createRoot();const o=!this.config.get("toolbar.shouldNotGroupWhenFull"),r=new d(this.locale,this.editing.view,{shouldToolbarGroupWhenFull:o});this.ui=new n(this,r),(0,u.attachToForm)(this)}destroy(){return this.sourceElement&&this.updateSourceElement(),this.ui.destroy(),super.destroy()}static create(e,t={}){return new Promise((o=>{const r=new this(e,t);o(r.initPlugins().then((()=>r.ui.init(N(e)?e:null))).then((()=>r.data.init(r.config.get("initialData")))).then((()=>r.fire("ready"))).then((()=>r)))}))}}function N(e){return R(e)}D.Context=u.Context,D.EditorWatchdog=h.EditorWatchdog,D.ContextWatchdog=h.ContextWatchdog})(),(window.CKEditor5=window.CKEditor5||{}).editorClassic=r})();