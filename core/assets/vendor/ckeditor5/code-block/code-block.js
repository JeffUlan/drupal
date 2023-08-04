!function(e){const t=e.en=e.en||{};t.dictionary=Object.assign(t.dictionary||{},{"Insert code block":"Insert code block","Plain text":"Plain text"})}(window.CKEDITOR_TRANSLATIONS||(window.CKEDITOR_TRANSLATIONS={})),
/*!
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md.
 */(()=>{var e={11:(e,t,n)=>{"use strict";n.d(t,{Z:()=>r});var o=n(609),i=n.n(o)()((function(e){return e[1]}));i.push([e.id,".ck-content pre{background:hsla(0,0%,78%,.3);border:1px solid #c4c4c4;border-radius:2px;color:#353535;direction:ltr;font-style:normal;min-width:200px;padding:1em;tab-size:4;text-align:left;white-space:pre-wrap}.ck-content pre code{background:unset;border-radius:0;padding:0}.ck.ck-editor__editable pre{position:relative}.ck.ck-editor__editable pre[data-language]:after{content:attr(data-language);position:absolute}:root{--ck-color-code-block-label-background:#757575}.ck.ck-editor__editable pre[data-language]:after{background:var(--ck-color-code-block-label-background);color:#fff;font-family:var(--ck-font-face);font-size:10px;line-height:16px;padding:var(--ck-spacing-tiny) var(--ck-spacing-medium);right:10px;top:-1px;white-space:nowrap}.ck.ck-code-block-dropdown .ck-dropdown__panel{max-height:250px;overflow-x:hidden;overflow-y:auto}",""]);const r=i},609:e=>{"use strict";e.exports=function(e){var t=[];return t.toString=function(){return this.map((function(t){var n=e(t);return t[2]?"@media ".concat(t[2]," {").concat(n,"}"):n})).join("")},t.i=function(e,n,o){"string"==typeof e&&(e=[[null,e,""]]);var i={};if(o)for(var r=0;r<this.length;r++){var a=this[r][0];null!=a&&(i[a]=!0)}for(var c=0;c<e.length;c++){var s=[].concat(e[c]);o&&i[s[0]]||(n&&(s[2]?s[2]="".concat(n," and ").concat(s[2]):s[2]=n),t.push(s))}},t}},62:(e,t,n)=>{"use strict";var o,i=function(){return void 0===o&&(o=Boolean(window&&document&&document.all&&!window.atob)),o},r=function(){var e={};return function(t){if(void 0===e[t]){var n=document.querySelector(t);if(window.HTMLIFrameElement&&n instanceof window.HTMLIFrameElement)try{n=n.contentDocument.head}catch(e){n=null}e[t]=n}return e[t]}}(),a=[];function c(e){for(var t=-1,n=0;n<a.length;n++)if(a[n].identifier===e){t=n;break}return t}function s(e,t){for(var n={},o=[],i=0;i<e.length;i++){var r=e[i],s=t.base?r[0]+t.base:r[0],l=n[s]||0,d="".concat(s," ").concat(l);n[s]=l+1;var u=c(d),g={css:r[1],media:r[2],sourceMap:r[3]};-1!==u?(a[u].references++,a[u].updater(g)):a.push({identifier:d,updater:h(g,t),references:1}),o.push(d)}return o}function l(e){var t=document.createElement("style"),o=e.attributes||{};if(void 0===o.nonce){var i=n.nc;i&&(o.nonce=i)}if(Object.keys(o).forEach((function(e){t.setAttribute(e,o[e])})),"function"==typeof e.insert)e.insert(t);else{var a=r(e.insert||"head");if(!a)throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");a.appendChild(t)}return t}var d,u=(d=[],function(e,t){return d[e]=t,d.filter(Boolean).join("\n")});function g(e,t,n,o){var i=n?"":o.media?"@media ".concat(o.media," {").concat(o.css,"}"):o.css;if(e.styleSheet)e.styleSheet.cssText=u(t,i);else{var r=document.createTextNode(i),a=e.childNodes;a[t]&&e.removeChild(a[t]),a.length?e.insertBefore(r,a[t]):e.appendChild(r)}}function f(e,t,n){var o=n.css,i=n.media,r=n.sourceMap;if(i?e.setAttribute("media",i):e.removeAttribute("media"),r&&"undefined"!=typeof btoa&&(o+="\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(r))))," */")),e.styleSheet)e.styleSheet.cssText=o;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(o))}}var m=null,p=0;function h(e,t){var n,o,i;if(t.singleton){var r=p++;n=m||(m=l(t)),o=g.bind(null,n,r,!1),i=g.bind(null,n,r,!0)}else n=l(t),o=f.bind(null,n,t),i=function(){!function(e){if(null===e.parentNode)return!1;e.parentNode.removeChild(e)}(n)};return o(e),function(t){if(t){if(t.css===e.css&&t.media===e.media&&t.sourceMap===e.sourceMap)return;o(e=t)}else i()}}e.exports=function(e,t){(t=t||{}).singleton||"boolean"==typeof t.singleton||(t.singleton=i());var n=s(e=e||[],t);return function(e){if(e=e||[],"[object Array]"===Object.prototype.toString.call(e)){for(var o=0;o<n.length;o++){var i=c(n[o]);a[i].references--}for(var r=s(e,t),l=0;l<n.length;l++){var d=c(n[l]);0===a[d].references&&(a[d].updater(),a.splice(d,1))}n=r}}}},704:(e,t,n)=>{e.exports=n(79)("./src/core.js")},492:(e,t,n)=>{e.exports=n(79)("./src/engine.js")},331:(e,t,n)=>{e.exports=n(79)("./src/enter.js")},273:(e,t,n)=>{e.exports=n(79)("./src/ui.js")},209:(e,t,n)=>{e.exports=n(79)("./src/utils.js")},79:e=>{"use strict";e.exports=CKEditor5.dll}},t={};function n(o){var i=t[o];if(void 0!==i)return i.exports;var r=t[o]={id:o,exports:{}};return e[o](r,r.exports,n),r.exports}n.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return n.d(t,{a:t}),t},n.d=(e,t)=>{for(var o in t)n.o(t,o)&&!n.o(e,o)&&Object.defineProperty(e,o,{enumerable:!0,get:t[o]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),n.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.nc=void 0;var o={};(()=>{"use strict";n.r(o),n.d(o,{CodeBlock:()=>E,CodeBlockEditing:()=>v,CodeBlockUI:()=>A});var e=n(704),t=n(331),i=n(492),r=n(209);function a(e){const t=e.t,n=e.config.get("codeBlock.languages");for(const e of n)"Plain text"===e.label&&(e.label=t("Plain text")),void 0===e.class&&(e.class=`language-${e.language}`);return n}function c(e,t,n){const o={};for(const i of e)if("class"===t){o[i[t].split(" ").shift()]=i[n]}else o[i[t]]=i[n];return o}function s(e){return e.data.match(/^(\s*)/)[0]}function l(e){const t=e.document.selection,n=[];if(t.isCollapsed)return[t.anchor];const o=t.getFirstRange().getWalker({ignoreElementEnd:!0,direction:"backward"});for(const{item:t}of o){if(!t.is("$textProxy"))continue;const{parent:o,startOffset:i}=t.textNode;if(!o.is("element","codeBlock"))continue;const r=s(t.textNode),a=e.createPositionAt(o,i+r.length);n.push(a)}return n}function d(e){const t=(0,r.first)(e.getSelectedBlocks());return!!t&&t.is("element","codeBlock")}function u(e,t){return!t.is("rootElement")&&!e.isLimit(t)&&e.checkChild(t.parent,"codeBlock")}class g extends e.Command{constructor(e){super(e),this._lastLanguage=null}refresh(){this.value=this._getValue(),this.isEnabled=this._checkEnabled()}execute(e={}){const t=this.editor,n=t.model,o=n.document.selection,i=a(t)[0],r=Array.from(o.getSelectedBlocks()),c=null==e.forceValue?!this.value:e.forceValue,s=function(e,t,n){if(e.language)return e.language;if(e.usePreviousLanguageChoice&&t)return t;return n}(e,this._lastLanguage,i.language);n.change((e=>{c?this._applyCodeBlock(e,r,s):this._removeCodeBlock(e,r)}))}_getValue(){const e=this.editor.model.document.selection,t=(0,r.first)(e.getSelectedBlocks());return!!!(!t||!t.is("element","codeBlock"))&&t.getAttribute("language")}_checkEnabled(){if(this.value)return!0;const e=this.editor.model.document.selection,t=this.editor.model.schema,n=(0,r.first)(e.getSelectedBlocks());return!!n&&u(t,n)}_applyCodeBlock(e,t,n){this._lastLanguage=n;const o=this.editor.model.schema,i=t.filter((e=>u(o,e)));for(const t of i)e.rename(t,"codeBlock"),e.setAttribute("language",n,t),o.removeDisallowedAttributes([t],e),Array.from(t.getChildren()).filter((e=>!o.checkChild(t,e))).forEach((t=>e.remove(t)));i.reverse().forEach(((t,n)=>{const o=i[n+1];t.previousSibling===o&&(e.appendElement("softBreak",o),e.merge(e.createPositionBefore(t)))}))}_removeCodeBlock(e,t){const n=t.filter((e=>e.is("element","codeBlock")));for(const t of n){const n=e.createRangeOn(t);for(const t of Array.from(n.getItems()).reverse())if(t.is("element","softBreak")&&t.parent.is("element","codeBlock")){const{position:n}=e.split(e.createPositionBefore(t)),o=n.nodeAfter;e.rename(o,"paragraph"),e.removeAttribute("language",o),e.remove(t)}e.rename(t,"paragraph"),e.removeAttribute("language",t)}}}class f extends e.Command{constructor(e){super(e),this._indentSequence=e.config.get("codeBlock.indentSequence")}refresh(){this.isEnabled=this._checkEnabled()}execute(){const e=this.editor.model;e.change((t=>{const n=l(e);for(const o of n){const n=t.createText(this._indentSequence);e.insertContent(n,o)}}))}_checkEnabled(){return!!this._indentSequence&&d(this.editor.model.document.selection)}}class m extends e.Command{constructor(e){super(e),this._indentSequence=e.config.get("codeBlock.indentSequence")}refresh(){this.isEnabled=this._checkEnabled()}execute(){const e=this.editor.model;e.change((()=>{const t=l(e);for(const n of t){const t=p(e,n,this._indentSequence);t&&e.deleteContent(e.createSelection(t))}}))}_checkEnabled(){if(!this._indentSequence)return!1;const e=this.editor.model;return!!d(e.document.selection)&&l(e).some((t=>p(e,t,this._indentSequence)))}}function p(e,t,n){const o=function(e){let t=e.parent.getChild(e.index);t&&!t.is("element","softBreak")||(t=e.nodeBefore);if(!t||t.is("element","softBreak"))return null;return t}(t);if(!o)return null;const i=s(o),r=i.lastIndexOf(n);if(r+n.length!==i.length)return null;if(-1===r)return null;const{parent:a,startOffset:c}=o;return e.createRange(e.createPositionAt(a,c+r),e.createPositionAt(a,c+r+n.length))}function h(e,t,n=!1){const o=c(t,"language","class"),i=c(t,"language","label");return(t,r,a)=>{const{writer:c,mapper:s,consumable:l}=a;if(!l.consume(r.item,"insert"))return;const d=r.item.getAttribute("language"),u=s.toViewPosition(e.createPositionBefore(r.item)),g={};n&&(g["data-language"]=i[d],g.spellcheck="false");const f=o[d]?{class:o[d]}:void 0,m=c.createContainerElement("code",f),p=c.createContainerElement("pre",g,m);c.insert(u,p),s.bindElements(r.item,m)}}const b="paragraph";class v extends e.Plugin{static get pluginName(){return"CodeBlockEditing"}static get requires(){return[t.ShiftEnter]}constructor(e){super(e),e.config.define("codeBlock",{languages:[{language:"plaintext",label:"Plain text"},{language:"c",label:"C"},{language:"cs",label:"C#"},{language:"cpp",label:"C++"},{language:"css",label:"CSS"},{language:"diff",label:"Diff"},{language:"html",label:"HTML"},{language:"java",label:"Java"},{language:"javascript",label:"JavaScript"},{language:"php",label:"PHP"},{language:"python",label:"Python"},{language:"ruby",label:"Ruby"},{language:"typescript",label:"TypeScript"},{language:"xml",label:"XML"}],indentSequence:"\t"})}init(){const e=this.editor,t=e.model.schema,n=e.model,o=e.editing.view,r=e.plugins.has("DocumentListEditing"),s=a(e);e.commands.add("codeBlock",new g(e)),e.commands.add("indentCodeBlock",new f(e)),e.commands.add("outdentCodeBlock",new m(e)),this.listenTo(o.document,"tab",((t,n)=>{const o=n.shiftKey?"outdentCodeBlock":"indentCodeBlock";e.commands.get(o).isEnabled&&(e.execute(o),n.stopPropagation(),n.preventDefault(),t.stop())}),{context:"pre"}),t.register("codeBlock",{allowWhere:"$block",allowChildren:"$text",isBlock:!0,allowAttributes:["language"]}),t.addAttributeCheck(((e,t)=>{const n=e.endsWith("codeBlock")&&t.startsWith("list")&&"list"!==t;return!(!r||!n)||!e.endsWith("codeBlock $text")&&void 0})),e.model.schema.addChildCheck(((e,t)=>{if(e.endsWith("codeBlock")&&t.isObject)return!1})),e.editing.downcastDispatcher.on("insert:codeBlock",h(n,s,!0)),e.data.downcastDispatcher.on("insert:codeBlock",h(n,s)),e.data.downcastDispatcher.on("insert:softBreak",function(e){return(t,n,o)=>{if("codeBlock"!==n.item.parent.name)return;const{writer:i,mapper:r,consumable:a}=o;if(!a.consume(n.item,"insert"))return;const c=r.toViewPosition(e.createPositionBefore(n.item));i.insert(c,i.createText("\n"))}}(n),{priority:"high"}),e.data.upcastDispatcher.on("element:code",function(e,t){const n=c(t,"class","language"),o=t[0].language;return(e,t,i)=>{const r=t.viewItem,a=r.parent;if(!a||!a.is("element","pre"))return;if(t.modelCursor.findAncestor("codeBlock"))return;const{consumable:c,writer:s}=i;if(!c.test(r,{name:!0}))return;const l=s.createElement("codeBlock"),d=[...r.getClassNames()];d.length||d.push("");for(const e of d){const t=n[e];if(t){s.setAttribute("language",t,l);break}}l.hasAttribute("language")||s.setAttribute("language",o,l),i.convertChildren(r,l),i.safeInsert(l,t.modelCursor)&&(c.consume(r,{name:!0}),i.updateConversionResult(l,t))}}(0,s)),e.data.upcastDispatcher.on("text",((e,t,{consumable:n,writer:o})=>{let i=t.modelCursor;if(!n.test(t.viewItem))return;if(!i.findAncestor("codeBlock"))return;n.consume(t.viewItem);const r=t.viewItem.data.split("\n").map((e=>o.createText(e))),a=r[r.length-1];for(const e of r)if(o.insert(e,i),i=i.getShiftedBy(e.offsetSize),e!==a){const e=o.createElement("softBreak");o.insert(e,i),i=o.createPositionAfter(e)}t.modelRange=o.createRange(t.modelCursor,i),t.modelCursor=i})),e.data.upcastDispatcher.on("element:pre",((e,t,{consumable:n})=>{const o=t.viewItem;if(o.findAncestor("pre"))return;const i=Array.from(o.getChildren()),r=i.find((e=>e.is("element","code")));if(r)for(const e of i)e!==r&&e.is("$text")&&n.consume(e,{name:!0})}),{priority:"high"}),this.listenTo(e.editing.view.document,"clipboardInput",((t,o)=>{let r=n.createRange(n.document.selection.anchor);if(o.targetRanges&&(r=e.editing.mapper.toModelRange(o.targetRanges[0])),!r.start.parent.is("element","codeBlock"))return;const a=o.dataTransfer.getData("text/plain"),c=new i.UpcastWriter(e.editing.view.document);o.content=function(e,t){const n=e.createDocumentFragment(),o=t.split("\n"),i=o.reduce(((t,n,i)=>(t.push(n),i<o.length-1&&t.push(e.createElement("br")),t)),[]);return e.appendChild(i,n),n}(c,a)})),this.listenTo(n,"getSelectedContent",((e,[o])=>{const i=o.anchor;!o.isCollapsed&&i.parent.is("element","codeBlock")&&i.hasSameParentAs(o.focus)&&n.change((n=>{const r=e.return;if(i.parent.is("element")&&(r.childCount>1||o.containsEntireContent(i.parent))){const t=n.createElement("codeBlock",i.parent.getAttributes());n.append(r,t);const o=n.createDocumentFragment();return n.append(t,o),void(e.return=o)}const a=r.getChild(0);t.checkAttribute(a,"code")&&n.setAttribute("code",!0,a)}))}))}afterInit(){const e=this.editor,t=e.commands,n=t.get("indent"),o=t.get("outdent");n&&n.registerChildCommand(t.get("indentCodeBlock"),{priority:"highest"}),o&&o.registerChildCommand(t.get("outdentCodeBlock")),this.listenTo(e.editing.view.document,"enter",((t,n)=>{e.model.document.selection.getLastPosition().parent.is("element","codeBlock")&&(function(e,t){const n=e.model,o=n.document,i=e.editing.view,r=o.selection.getLastPosition(),a=r.nodeAfter;if(t||!o.selection.isCollapsed||!r.isAtStart)return!1;if(!B(a))return!1;return e.model.change((t=>{e.execute("enter");const n=o.selection.anchor.parent.previousSibling;t.rename(n,b),t.setSelection(n,"in"),e.model.schema.removeDisallowedAttributes([n],t),t.remove(a)})),i.scrollToTheSelection(),!0}(e,n.isSoft)||function(e,t){const n=e.model,o=n.document,i=e.editing.view,r=o.selection.getLastPosition(),a=r.nodeBefore;let c;if(t||!o.selection.isCollapsed||!r.isAtEnd||!a||!a.previousSibling)return!1;if(B(a)&&B(a.previousSibling))c=n.createRange(n.createPositionBefore(a.previousSibling),n.createPositionAfter(a));else if(k(a)&&B(a.previousSibling)&&B(a.previousSibling.previousSibling))c=n.createRange(n.createPositionBefore(a.previousSibling.previousSibling),n.createPositionAfter(a));else{if(!(k(a)&&B(a.previousSibling)&&k(a.previousSibling.previousSibling)&&a.previousSibling.previousSibling&&B(a.previousSibling.previousSibling.previousSibling)))return!1;c=n.createRange(n.createPositionBefore(a.previousSibling.previousSibling.previousSibling),n.createPositionAfter(a))}return e.model.change((t=>{t.remove(c),e.execute("enter");const n=o.selection.anchor.parent;t.rename(n,b),e.model.schema.removeDisallowedAttributes([n],t)})),i.scrollToTheSelection(),!0}(e,n.isSoft)||function(e){const t=e.model,n=t.document,o=n.selection.getLastPosition(),i=o.nodeBefore||o.textNode;let r;i&&i.is("$text")&&(r=s(i));e.model.change((t=>{e.execute("shiftEnter"),r&&t.insertText(r,n.selection.anchor)}))}(e),n.preventDefault(),t.stop())}),{context:"pre"})}}function k(e){return e&&e.is("$text")&&!e.data.match(/\S/)}function B(e){return e&&e.is("element","softBreak")}var C=n(273);var w=n(62),x=n.n(w),S=n(11),y={injectType:"singletonStyleTag",attributes:{"data-cke":!0},insert:"head",singleton:!0};x()(S.Z,y);S.Z.locals;class A extends e.Plugin{static get pluginName(){return"CodeBlockUI"}init(){const e=this.editor,t=e.t,n=e.ui.componentFactory,o=a(e);n.add("codeBlock",(n=>{const i=e.commands.get("codeBlock"),r=(0,C.createDropdown)(n,C.SplitButtonView),a=r.buttonView,c=t("Insert code block");return a.set({label:c,tooltip:!0,icon:'<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M12.87 12.61a.75.75 0 0 1-.089.976l-.085.07-3.154 2.254 3.412 2.414a.75.75 0 0 1 .237.95l-.057.095a.75.75 0 0 1-.95.237l-.096-.058-4.272-3.022-.003-1.223 4.01-2.867a.75.75 0 0 1 1.047.174zm2.795-.231.095.057 4.011 2.867-.003 1.223-4.272 3.022-.095.058a.75.75 0 0 1-.88-.151l-.07-.086-.058-.095a.75.75 0 0 1 .15-.88l.087-.07 3.412-2.414-3.154-2.253-.085-.071a.75.75 0 0 1 .862-1.207zM16 0a2 2 0 0 1 2 2v9.354l-.663-.492-.837-.001V2a.5.5 0 0 0-.5-.5H2a.5.5 0 0 0-.5.5v15a.5.5 0 0 0 .5.5h3.118L7.156 19H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h14zM5.009 15l.003 1H3v-1h2.009zm2.188-2-1.471 1H5v-1h2.197zM10 11v.095L8.668 12H7v-1h3zm4-2v1H7V9h7zm0-2v1H7V7h7zm-4-2v1H5V5h5zM6 3v1H3V3h3z"/></svg>',isToggleable:!0}),a.bind("isOn").to(i,"value",(e=>!!e)),a.on("execute",(()=>{e.execute("codeBlock",{usePreviousLanguageChoice:!0}),e.editing.view.focus()})),r.on("execute",(t=>{e.execute("codeBlock",{language:t.source._codeBlockLanguage,forceValue:!0}),e.editing.view.focus()})),r.class="ck-code-block-dropdown",r.bind("isEnabled").to(i),(0,C.addListToDropdown)(r,(()=>this._getLanguageListItemDefinitions(o)),{role:"menu",ariaLabel:c}),r}))}_getLanguageListItemDefinitions(e){const t=this.editor.commands.get("codeBlock"),n=new r.Collection;for(const o of e){const e={type:"button",model:new C.Model({_codeBlockLanguage:o.language,label:o.label,role:"menuitemradio",withText:!0})};e.model.bind("isOn").to(t,"value",(t=>t===e.model._codeBlockLanguage)),n.add(e)}return n}}class E extends e.Plugin{static get requires(){return[v,A]}static get pluginName(){return"CodeBlock"}}})(),(window.CKEditor5=window.CKEditor5||{}).codeBlock=o})();