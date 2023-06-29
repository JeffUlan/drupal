/*!
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md.
 */(()=>{var e={945:(e,t,n)=>{e.exports=n(79)("./src/clipboard.js")},704:(e,t,n)=>{e.exports=n(79)("./src/core.js")},492:(e,t,n)=>{e.exports=n(79)("./src/engine.js")},79:e=>{"use strict";e.exports=CKEditor5.dll}},t={};function n(r){var i=t[r];if(void 0!==i)return i.exports;var s=t[r]={exports:{}};return e[r](s,s.exports,n),s.exports}n.d=(e,t)=>{for(var r in t)n.o(t,r)&&!n.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:t[r]})},n.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),n.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})};var r={};(()=>{"use strict";n.r(r),n.d(r,{PasteFromOffice:()=>C,parseHtml:()=>x});var e=n(704),t=n(945),i=n(492);function s(e,t){if(!e.childCount)return;const n=new i.UpcastWriter(e.document),r=function(e,t){const n=t.createRangeIn(e),r=new i.Matcher({name:/^p|h\d+$/,styles:{"mso-list":/.*/}}),s=[];for(const e of n)if("elementStart"===e.type&&r.match(e.item)){const t=l(e.item);s.push({element:e.item,id:t.id,order:t.order,indent:t.indent})}return s}(e,n);if(!r.length)return;let s=null,a=1;r.forEach(((e,l)=>{const u=function(e,t){if(!e)return!0;if(e.id!==t.id)return t.indent-e.indent!=1;const n=t.element.previousSibling;if(!n)return!0;return r=n,!(r.is("element","ol")||r.is("element","ul"));var r}(r[l-1],e),f=u?null:r[l-1],m=(p=e,(d=f)?p.indent-d.indent:p.indent-1);var d,p;if(u&&(s=null,a=1),!s||0!==m){const r=function(e,t){const n=new RegExp(`@list l${e.id}:level${e.indent}\\s*({[^}]*)`,"gi"),r=/mso-level-number-format:([^;]{0,100});/gi,i=/mso-level-start-at:\s{0,100}([0-9]{0,10})\s{0,100};/gi,s=n.exec(t);let c="decimal",l="ol",a=null;if(s&&s[1]){const t=r.exec(s[1]);if(t&&t[1]&&(c=t[1].trim(),l="bullet"!==c&&"image"!==c?"ol":"ul"),"bullet"===c){const t=function(e){const t=function(e){if(e.getChild(0).is("$text"))return null;for(const t of e.getChildren()){if(!t.is("element","span"))continue;const e=t.getChild(0);if(e)return e.is("$text")?e:e.getChild(0)}return null}(e);if(!t)return null;const n=t._data;if("o"===n)return"circle";if("·"===n)return"disc";if("§"===n)return"square";return null}(e.element);t&&(c=t)}else{const e=i.exec(s[1]);e&&e[1]&&(a=parseInt(e[1]))}}return{type:l,startIndex:a,style:o(c)}}(e,t);if(s){if(e.indent>a){const e=s.getChild(s.childCount-1),t=e.getChild(e.childCount-1);s=c(r,t,n),a+=1}else if(e.indent<a){const t=a-e.indent;s=function(e,t){const n=e.getAncestors({parentFirst:!0});let r=null,i=0;for(const e of n)if((e.is("element","ul")||e.is("element","ol"))&&i++,i===t){r=e;break}return r}(s,t),a=e.indent}}else s=c(r,e.element,n);e.indent<=a&&(s.is("element",r.type)||(s=n.rename(r.type,s)))}const g=function(e,t){return function(e,t){const n=new i.Matcher({name:"span",styles:{"mso-list":"Ignore"}}),r=t.createRangeIn(e);for(const e of r)"elementStart"===e.type&&n.match(e.item)&&t.remove(e.item)}(e,t),t.removeStyle("text-indent",e),t.rename("li",e)}(e.element,n);n.appendChild(g,s)}))}function o(e){if(e.startsWith("arabic-leading-zero"))return"decimal-leading-zero";switch(e){case"alpha-upper":return"upper-alpha";case"alpha-lower":return"lower-alpha";case"roman-upper":return"upper-roman";case"roman-lower":return"lower-roman";case"circle":case"disc":case"square":return e;default:return null}}function c(e,t,n){const r=t.parent,i=n.createElement(e.type),s=r.getChildIndex(t)+1;return n.insertChild(s,i,r),e.style&&n.setStyle("list-style-type",e.style,i),e.startIndex&&e.startIndex>1&&n.setAttribute("start",e.startIndex,i),i}function l(e){const t={},n=e.getStyle("mso-list");if(n){const e=n.match(/(^|\s{1,100})l(\d+)/i),r=n.match(/\s{0,100}lfo(\d+)/i),i=n.match(/\s{0,100}level(\d+)/i);e&&r&&i&&(t.id=e[2],t.order=r[1],t.indent=parseInt(i[1]))}return t}function a(e,t){if(!e.childCount)return;const n=new i.UpcastWriter(e.document),r=function(e,t){const n=t.createRangeIn(e),r=new i.Matcher({name:/v:(.+)/}),s=[];for(const e of n){if("elementStart"!=e.type)continue;const t=e.item,n=t.previousSibling,i=n&&n.is("element")?n.name:null;r.match(t)&&t.getAttribute("o:gfxdata")&&"v:shapetype"!==i&&s.push(e.item.getAttribute("id"))}return s}(e,n);!function(e,t,n){const r=n.createRangeIn(t),s=new i.Matcher({name:"img"}),o=[];for(const t of r)if(t.item.is("element")&&s.match(t.item)){const n=t.item,r=n.getAttribute("v:shapes")?n.getAttribute("v:shapes").split(" "):[];r.length&&r.every((t=>e.indexOf(t)>-1))?o.push(n):n.getAttribute("src")||o.push(n)}for(const e of o)n.remove(e)}(r,e,n),function(e,t,n){const r=n.createRangeIn(t),i=[];for(const t of r)if("elementStart"==t.type&&t.item.is("element","v:shape")){const n=t.item.getAttribute("id");if(e.includes(n))continue;s(t.item.parent.getChildren(),n)||i.push(t.item)}for(const e of i){const t={src:o(e)};e.hasAttribute("alt")&&(t.alt=e.getAttribute("alt"));const r=n.createElement("img",t);n.insertChild(e.index+1,r,e.parent)}function s(e,t){for(const n of e)if(n.is("element")){if("img"==n.name&&n.getAttribute("v:shapes")==t)return!0;if(s(n.getChildren(),t))return!0}return!1}function o(e){for(const t of e.getChildren())if(t.is("element")&&t.getAttribute("src"))return t.getAttribute("src")}}(r,e,n),function(e,t){const n=t.createRangeIn(e),r=new i.Matcher({name:/v:(.+)/}),s=[];for(const e of n)"elementStart"==e.type&&r.match(e.item)&&s.push(e.item);for(const e of s)t.remove(e)}(e,n);const s=function(e,t){const n=t.createRangeIn(e),r=new i.Matcher({name:"img"}),s=[];for(const e of n)e.item.is("element")&&r.match(e.item)&&e.item.getAttribute("src").startsWith("file://")&&s.push(e.item);return s}(e,n);s.length&&function(e,t,n){if(e.length===t.length)for(let r=0;r<e.length;r++){const i=`data:${t[r].type};base64,${u(t[r].hex)}`;n.setAttribute("src",i,e[r])}}(s,function(e){if(!e)return[];const t=/{\\pict[\s\S]+?\\bliptag-?\d+(\\blipupi-?\d+)?({\\\*\\blipuid\s?[\da-fA-F]+)?[\s}]*?/,n=new RegExp("(?:("+t.source+"))([\\da-fA-F\\s]+)\\}","g"),r=e.match(n),i=[];if(r)for(const e of r){let n=!1;e.includes("\\pngblip")?n="image/png":e.includes("\\jpegblip")&&(n="image/jpeg"),n&&i.push({hex:e.replace(t,"").replace(/[^\da-fA-F]/g,""),type:n})}return i}(t),n)}function u(e){return btoa(e.match(/\w{2}/g).map((e=>String.fromCharCode(parseInt(e,16)))).join(""))}const f=/<meta\s*name="?generator"?\s*content="?microsoft\s*word\s*\d+"?\/?>/i,m=/xmlns:o="urn:schemas-microsoft-com/i;class d{constructor(e){this.document=e}isActive(e){return f.test(e)||m.test(e)}execute(e){const{body:t,stylesString:n}=e._parsedData;s(t,n),a(t,e.dataTransfer.getData("text/rtf")),e.content=t}}function p(e,t,n,{blockElements:r,inlineObjectElements:i}){let s=n.createPositionAt(e,"forward"==t?"after":"before");return s=s.getLastMatchingPosition((({item:e})=>e.is("element")&&!r.includes(e.name)&&!i.includes(e.name)),{direction:t}),"forward"==t?s.nodeAfter:s.nodeBefore}function g(e,t){return!!e&&e.is("element")&&t.includes(e.name)}const h=/id=("|')docs-internal-guid-[-0-9a-f]+("|')/i;class b{constructor(e){this.document=e}isActive(e){return h.test(e)}execute(e){const t=new i.UpcastWriter(this.document),{body:n}=e._parsedData;!function(e,t){for(const n of e.getChildren())if(n.is("element","b")&&"normal"===n.getStyle("font-weight")){const r=e.getChildIndex(n);t.remove(n),t.insertChild(r,n.getChildren(),e)}}(n,t),function(e,t){for(const n of t.createRangeIn(e)){const e=n.item;if(e.is("element","li")){const n=e.getChild(0);n&&n.is("element","p")&&t.unwrapElement(n)}}}(n,t),function(e,t){const n=new i.ViewDocument(t.document.stylesProcessor),r=new i.DomConverter(n,{renderingMode:"data"}),s=r.blockElements,o=r.inlineObjectElements,c=[];for(const n of t.createRangeIn(e)){const e=n.item;if(e.is("element","br")){const n=p(e,"forward",t,{blockElements:s,inlineObjectElements:o}),r=p(e,"backward",t,{blockElements:s,inlineObjectElements:o}),i=g(n,s);(g(r,s)||i)&&c.push(e)}}for(const e of c)e.hasClass("Apple-interchange-newline")?t.remove(e):t.replace(e,t.createElement("p"))}(n,t),e.content=n}}const y=/<google-sheets-html-origin/i;class w{constructor(e){this.document=e}isActive(e){return y.test(e)}execute(e){const t=new i.UpcastWriter(this.document),{body:n}=e._parsedData;!function(e,t){for(const n of e.getChildren())if(n.is("element","google-sheets-html-origin")){const r=e.getChildIndex(n);t.remove(n),t.insertChild(r,n.getChildren(),e)}}(n,t),function(e,t){for(const n of e.getChildren())n.is("element","table")&&n.hasAttribute("xmlns")&&t.removeAttribute("xmlns",n)}(n,t),function(e,t){for(const n of e.getChildren())n.is("element","table")&&"0px"===n.getStyle("width")&&t.removeStyle("width",n)}(n,t),function(e,t){for(const n of Array.from(e.getChildren()))n.is("element","style")&&t.remove(n)}(n,t),e.content=n}}function v(e){return e.replace(/<span(?: class="Apple-converted-space"|)>(\s+)<\/span>/g,((e,t)=>1===t.length?" ":Array(t.length+1).join("  ").substr(0,t.length)))}function x(e,t){const n=new DOMParser,r=function(e){return v(v(e)).replace(/(<span\s+style=['"]mso-spacerun:yes['"]>[^\S\r\n]*?)[\r\n]+([^\S\r\n]*<\/span>)/g,"$1$2").replace(/<span\s+style=['"]mso-spacerun:yes['"]><\/span>/g,"").replace(/ <\//g," </").replace(/ <o:p><\/o:p>/g," <o:p></o:p>").replace(/<o:p>(&nbsp;|\u00A0)<\/o:p>/g,"").replace(/>([^\S\r\n]*[\r\n]\s*)</g,"><")}(function(e){const t="</body>",n="</html>",r=e.indexOf(t);if(r<0)return e;const i=e.indexOf(n,r+t.length);return e.substring(0,r+t.length)+(i>=0?e.substring(i):"")}(e=e.replace(/<!--\[if gte vml 1]>/g,""))),s=n.parseFromString(r,"text/html");!function(e){e.querySelectorAll("span[style*=spacerun]").forEach((e=>{const t=e,n=t.innerText.length||0;t.innerText=Array(n+1).join("  ").substr(0,n)}))}(s);const o=s.body.innerHTML,c=function(e,t){const n=new i.ViewDocument(t),r=new i.DomConverter(n,{renderingMode:"data"}),s=e.createDocumentFragment(),o=e.body.childNodes;for(;o.length>0;)s.appendChild(o[0]);return r.domToView(s,{skipComments:!0})}(s,t),l=function(e){const t=[],n=[],r=Array.from(e.getElementsByTagName("style"));for(const e of r)e.sheet&&e.sheet.cssRules&&e.sheet.cssRules.length&&(t.push(e.sheet),n.push(e.innerHTML));return{styles:t,stylesString:n.join(" ")}}(s);return{body:c,bodyString:o,styles:l.styles,stylesString:l.stylesString}}class C extends e.Plugin{static get pluginName(){return"PasteFromOffice"}static get requires(){return[t.ClipboardPipeline]}init(){const e=this.editor,t=e.plugins.get("ClipboardPipeline"),n=e.editing.view.document,r=[];r.push(new d(n)),r.push(new b(n)),r.push(new w(n)),t.on("inputTransformation",((t,i)=>{if(i._isTransformedWithPasteFromOffice)return;if(e.model.document.selection.getFirstPosition().parent.is("element","codeBlock"))return;const s=i.dataTransfer.getData("text/html"),o=r.find((e=>e.isActive(s)));o&&(i._parsedData||(i._parsedData=x(s,n.stylesProcessor)),o.execute(i),i._isTransformedWithPasteFromOffice=!0)}),{priority:"high"})}}})(),(window.CKEditor5=window.CKEditor5||{}).pasteFromOffice=r})();