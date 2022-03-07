!function(t){const e=t.en=t.en||{};e.dictionary=Object.assign(e.dictionary||{},{"Remove Format":"Remove Format"})}(window.CKEDITOR_TRANSLATIONS||(window.CKEDITOR_TRANSLATIONS={})),
/*!
 * @license Copyright (c) 2003-2022, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md.
 */(()=>{var t={704:(t,e,o)=>{t.exports=o(79)("./src/core.js")},273:(t,e,o)=>{t.exports=o(79)("./src/ui.js")},209:(t,e,o)=>{t.exports=o(79)("./src/utils.js")},79:t=>{"use strict";t.exports=CKEditor5.dll}},e={};function o(r){var i=e[r];if(void 0!==i)return i.exports;var s=e[r]={exports:{}};return t[r](s,s.exports,o),s.exports}o.d=(t,e)=>{for(var r in e)o.o(e,r)&&!o.o(t,r)&&Object.defineProperty(t,r,{enumerable:!0,get:e[r]})},o.o=(t,e)=>Object.prototype.hasOwnProperty.call(t,e),o.r=t=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})};var r={};(()=>{"use strict";o.r(r),o.d(r,{RemoveFormat:()=>m,RemoveFormatEditing:()=>c,RemoveFormatUI:()=>s});var t=o(704),e=o(273);const i="removeFormat";class s extends t.Plugin{static get pluginName(){return"RemoveFormatUI"}init(){const t=this.editor,o=t.t;t.ui.componentFactory.add(i,(r=>{const s=t.commands.get(i),n=new e.ButtonView(r);return n.set({label:o("Remove Format"),icon:'<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M8.69 14.915c.053.052.173.083.36.093a.366.366 0 0 1 .345.485l-.003.01a.738.738 0 0 1-.697.497h-2.67a.374.374 0 0 1-.353-.496l.013-.038a.681.681 0 0 1 .644-.458c.197-.012.325-.043.386-.093a.28.28 0 0 0 .072-.11L9.592 4.5H6.269c-.359-.017-.609.013-.75.09-.142.078-.289.265-.442.563-.192.29-.516.464-.864.464H4.17a.43.43 0 0 1-.407-.569L4.46 3h13.08l-.62 2.043a.81.81 0 0 1-.775.574h-.114a.486.486 0 0 1-.486-.486c.001-.284-.054-.464-.167-.54-.112-.076-.367-.106-.766-.091h-3.28l-2.68 10.257c-.006.074.007.127.038.158zM3 17h8a.5.5 0 1 1 0 1H3a.5.5 0 1 1 0-1zm11.299 1.17a.75.75 0 1 1-1.06-1.06l1.414-1.415-1.415-1.414a.75.75 0 0 1 1.06-1.06l1.415 1.414 1.414-1.415a.75.75 0 1 1 1.06 1.06l-1.413 1.415 1.414 1.415a.75.75 0 0 1-1.06 1.06l-1.415-1.414-1.414 1.414z"/></svg>',tooltip:!0}),n.bind("isOn","isEnabled").to(s,"value","isEnabled"),this.listenTo(n,"execute",(()=>{t.execute(i),t.editing.view.focus()})),n}))}}var n=o(209);class a extends t.Command{refresh(){const t=this.editor.model;this.isEnabled=!!(0,n.first)(this._getFormattingItems(t.document.selection,t.schema))}execute(){const t=this.editor.model,e=t.schema;t.change((o=>{for(const r of this._getFormattingItems(t.document.selection,e))if(r.is("selection"))for(const t of this._getFormattingAttributes(r,e))o.removeSelectionAttribute(t);else{const t=o.createRangeOn(r);for(const i of this._getFormattingAttributes(r,e))o.removeAttribute(i,t)}}))}*_getFormattingItems(t,e){const o=t=>!!(0,n.first)(this._getFormattingAttributes(t,e));for(const r of t.getRanges())for(const t of r.getItems())!e.isBlock(t)&&o(t)&&(yield t);for(const e of t.getSelectedBlocks())o(e)&&(yield e);o(t)&&(yield t)}*_getFormattingAttributes(t,e){for(const[o]of t.getAttributes()){const t=e.getAttributeProperties(o);t&&t.isFormatting&&(yield o)}}}class c extends t.Plugin{static get pluginName(){return"RemoveFormatEditing"}init(){const t=this.editor;t.commands.add("removeFormat",new a(t))}}class m extends t.Plugin{static get requires(){return[c,s]}static get pluginName(){return"RemoveFormat"}}})(),(window.CKEditor5=window.CKEditor5||{}).removeFormat=r})();