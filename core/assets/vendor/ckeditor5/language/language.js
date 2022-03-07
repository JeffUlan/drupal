!function(e){const t=e.en=e.en||{};t.dictionary=Object.assign(t.dictionary||{},{"Choose language":"Choose language",Language:"Language","Remove language":"Remove language"})}(window.CKEDITOR_TRANSLATIONS||(window.CKEDITOR_TRANSLATIONS={})),
/*!
 * @license Copyright (c) 2003-2022, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md.
 */(()=>{var e={176:(e,t,n)=>{"use strict";n.d(t,{Z:()=>o});var a=n(609),r=n.n(a)()((function(e){return e[1]}));r.push([e.id,".ck-content span[lang]{font-style:italic}",""]);const o=r},609:e=>{"use strict";e.exports=function(e){var t=[];return t.toString=function(){return this.map((function(t){var n=e(t);return t[2]?"@media ".concat(t[2]," {").concat(n,"}"):n})).join("")},t.i=function(e,n,a){"string"==typeof e&&(e=[[null,e,""]]);var r={};if(a)for(var o=0;o<this.length;o++){var i=this[o][0];null!=i&&(r[i]=!0)}for(var s=0;s<e.length;s++){var u=[].concat(e[s]);a&&r[u[0]]||(n&&(u[2]?u[2]="".concat(n," and ").concat(u[2]):u[2]=n),t.push(u))}},t}},62:(e,t,n)=>{"use strict";var a,r=function(){return void 0===a&&(a=Boolean(window&&document&&document.all&&!window.atob)),a},o=function(){var e={};return function(t){if(void 0===e[t]){var n=document.querySelector(t);if(window.HTMLIFrameElement&&n instanceof window.HTMLIFrameElement)try{n=n.contentDocument.head}catch(e){n=null}e[t]=n}return e[t]}}(),i=[];function s(e){for(var t=-1,n=0;n<i.length;n++)if(i[n].identifier===e){t=n;break}return t}function u(e,t){for(var n={},a=[],r=0;r<e.length;r++){var o=e[r],u=t.base?o[0]+t.base:o[0],l=n[u]||0,c="".concat(u," ").concat(l);n[u]=l+1;var d=s(c),g={css:o[1],media:o[2],sourceMap:o[3]};-1!==d?(i[d].references++,i[d].updater(g)):i.push({identifier:c,updater:h(g,t),references:1}),a.push(c)}return a}function l(e){var t=document.createElement("style"),a=e.attributes||{};if(void 0===a.nonce){var r=n.nc;r&&(a.nonce=r)}if(Object.keys(a).forEach((function(e){t.setAttribute(e,a[e])})),"function"==typeof e.insert)e.insert(t);else{var i=o(e.insert||"head");if(!i)throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");i.appendChild(t)}return t}var c,d=(c=[],function(e,t){return c[e]=t,c.filter(Boolean).join("\n")});function g(e,t,n,a){var r=n?"":a.media?"@media ".concat(a.media," {").concat(a.css,"}"):a.css;if(e.styleSheet)e.styleSheet.cssText=d(t,r);else{var o=document.createTextNode(r),i=e.childNodes;i[t]&&e.removeChild(i[t]),i.length?e.insertBefore(o,i[t]):e.appendChild(o)}}function f(e,t,n){var a=n.css,r=n.media,o=n.sourceMap;if(r?e.setAttribute("media",r):e.removeAttribute("media"),o&&"undefined"!=typeof btoa&&(a+="\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(o))))," */")),e.styleSheet)e.styleSheet.cssText=a;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(a))}}var p=null,m=0;function h(e,t){var n,a,r;if(t.singleton){var o=m++;n=p||(p=l(t)),a=g.bind(null,n,o,!1),r=g.bind(null,n,o,!0)}else n=l(t),a=f.bind(null,n,t),r=function(){!function(e){if(null===e.parentNode)return!1;e.parentNode.removeChild(e)}(n)};return a(e),function(t){if(t){if(t.css===e.css&&t.media===e.media&&t.sourceMap===e.sourceMap)return;a(e=t)}else r()}}e.exports=function(e,t){(t=t||{}).singleton||"boolean"==typeof t.singleton||(t.singleton=r());var n=u(e=e||[],t);return function(e){if(e=e||[],"[object Array]"===Object.prototype.toString.call(e)){for(var a=0;a<n.length;a++){var r=s(n[a]);i[r].references--}for(var o=u(e,t),l=0;l<n.length;l++){var c=s(n[l]);0===i[c].references&&(i[c].updater(),i.splice(c,1))}n=o}}}},704:(e,t,n)=>{e.exports=n(79)("./src/core.js")},273:(e,t,n)=>{e.exports=n(79)("./src/ui.js")},209:(e,t,n)=>{e.exports=n(79)("./src/utils.js")},79:e=>{"use strict";e.exports=CKEditor5.dll}},t={};function n(a){var r=t[a];if(void 0!==r)return r.exports;var o=t[a]={id:a,exports:{}};return e[a](o,o.exports,n),o.exports}n.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return n.d(t,{a:t}),t},n.d=(e,t)=>{for(var a in t)n.o(t,a)&&!n.o(e,a)&&Object.defineProperty(e,a,{enumerable:!0,get:t[a]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),n.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})};var a={};(()=>{"use strict";n.r(a),n.d(a,{TextPartLanguage:()=>f,TextPartLanguageEditing:()=>i,TextPartLanguageUI:()=>g});var e=n(704),t=n(209);function r(e,n){return`${e}:${n=n||(0,t.getLanguageDirection)(e)}`}class o extends e.Command{refresh(){const e=this.editor.model,t=e.document;this.value=this._getValueFromFirstAllowedNode(),this.isEnabled=e.schema.checkAttributeInSelection(t.selection,"language")}execute({languageCode:e,textDirection:t}={}){const n=this.editor.model,a=n.document.selection,o=!!e&&r(e,t);n.change((e=>{if(a.isCollapsed)o?e.setSelectionAttribute("language",o):e.removeSelectionAttribute("language");else{const t=n.schema.getValidRanges(a.getRanges(),"language");for(const n of t)o?e.setAttribute("language",o,n):e.removeAttribute("language",n)}}))}_getValueFromFirstAllowedNode(){const e=this.editor.model,t=e.schema,n=e.document.selection;if(n.isCollapsed)return n.getAttribute("language")||!1;for(const e of n.getRanges())for(const n of e.getItems())if(t.checkAttribute(n,"language"))return n.getAttribute("language")||!1;return!1}}class i extends e.Plugin{static get pluginName(){return"TextPartLanguageEditing"}constructor(e){super(e),e.config.define("language",{textPartLanguage:[{title:"Arabic",languageCode:"ar"},{title:"French",languageCode:"fr"},{title:"Spanish",languageCode:"es"}]})}init(){const e=this.editor;e.model.schema.extend("$text",{allowAttributes:"language"}),e.model.schema.setAttributeProperties("language",{copyOnEnter:!0}),this._defineConverters(),e.commands.add("textPartLanguage",new o(e))}_defineConverters(){const e=this.editor.conversion;e.for("upcast").elementToAttribute({model:{key:"language",value:e=>r(e.getAttribute("lang"),e.getAttribute("dir"))},view:{name:"span",attributes:{lang:/[\s\S]+/}}}),e.for("downcast").attributeToElement({model:"language",view:(e,{writer:t})=>{if(!e)return;const{languageCode:n,textDirection:a}=function(e){const[t,n]=e.split(":");return{languageCode:t,textDirection:n}}(e);return t.createAttributeElement("span",{lang:n,dir:a})}})}}var s=n(273),u=n(62),l=n.n(u),c=n(176),d={injectType:"singletonStyleTag",attributes:{"data-cke":!0},insert:"head",singleton:!0};l()(c.Z,d);c.Z.locals;class g extends e.Plugin{static get pluginName(){return"TextPartLanguageUI"}init(){const e=this.editor,n=e.t,a=e.config.get("language.textPartLanguage"),o=n("Choose language"),i=n("Remove language"),u=n("Language");e.ui.componentFactory.add("textPartLanguage",(n=>{const l=new t.Collection,c={},d=e.commands.get("textPartLanguage");l.add({type:"button",model:new s.Model({label:i,languageCode:!1,withText:!0})}),l.add({type:"separator"});for(const e of a){const t={type:"button",model:new s.Model({label:e.title,languageCode:e.languageCode,textDirection:e.textDirection,withText:!0})},n=r(e.languageCode,e.textDirection);t.model.bind("isOn").to(d,"value",(e=>e===n)),l.add(t),c[n]=e.title}const g=(0,s.createDropdown)(n);return(0,s.addListToDropdown)(g,l),g.buttonView.set({isOn:!1,withText:!0,tooltip:u}),g.extendTemplate({attributes:{class:["ck-text-fragment-language-dropdown"]}}),g.bind("isEnabled").to(d,"isEnabled"),g.buttonView.bind("label").to(d,"value",(e=>c[e]||o)),this.listenTo(g,"execute",(t=>{d.execute({languageCode:t.source.languageCode,textDirection:t.source.textDirection}),e.editing.view.focus()})),g}))}}class f extends e.Plugin{static get requires(){return[i,g]}static get pluginName(){return"TextPartLanguage"}}})(),(window.CKEditor5=window.CKEditor5||{}).language=a})();