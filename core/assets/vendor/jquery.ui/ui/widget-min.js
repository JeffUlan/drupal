/*!
 * jQuery UI Widget 1.11.4
 * http://jqueryui.com
 *
 * Copyright jQuery Foundation and other contributors
 * Released under the MIT license.
 * http://jquery.org/license
 *
 * http://api.jqueryui.com/jQuery.widget/
 */(function(e){typeof define=="function"&&define.amd?define(["jquery"],e):e(jQuery)})(function(e){var t=0,n=Array.prototype.slice;return e.cleanData=function(t){return function(n){var r,i,s;for(s=0;(i=n[s])!=null;s++)try{r=e._data(i,"events"),r&&r.remove&&e(i).triggerHandler("remove")}catch(o){}t(n)}}(e.cleanData),e.widget=function(t,n,r){var i,s,o,u,a={},f=t.split(".")[0];return t=t.split(".")[1],i=f+"-"+t,r||(r=n,n=e.Widget),e.expr[":"][i.toLowerCase()]=function(t){return!!e.data(t,i)},e[f]=e[f]||{},s=e[f][t],o=e[f][t]=function(e,t){if(!this._createWidget)return new o(e,t);arguments.length&&this._createWidget(e,t)},e.extend(o,s,{version:r.version,_proto:e.extend({},r),_childConstructors:[]}),u=new n,u.options=e.widget.extend({},u.options),e.each(r,function(t,r){if(!e.isFunction(r)){a[t]=r;return}a[t]=function(){var e=function(){return n.prototype[t].apply(this,arguments)},i=function(e){return n.prototype[t].apply(this,e)};return function(){var t=this._super,n=this._superApply,s;return this._super=e,this._superApply=i,s=r.apply(this,arguments),this._super=t,this._superApply=n,s}}()}),o.prototype=e.widget.extend(u,{widgetEventPrefix:s?u.widgetEventPrefix||t:t},a,{constructor:o,namespace:f,widgetName:t,widgetFullName:i}),s?(e.each(s._childConstructors,function(t,n){var r=n.prototype;e.widget(r.namespace+"."+r.widgetName,o,n._proto)}),delete s._childConstructors):n._childConstructors.push(o),e.widget.bridge(t,o),o},e.widget.extend=function(t){var r=n.call(arguments,1),i=0,s=r.length,o,u;for(;i<s;i++)for(o in r[i])u=r[i][o],r[i].hasOwnProperty(o)&&u!==undefined&&(e.isPlainObject(u)?t[o]=e.isPlainObject(t[o])?e.widget.extend({},t[o],u):e.widget.extend({},u):t[o]=u);return t},e.widget.bridge=function(t,r){var i=r.prototype.widgetFullName||t;e.fn[t]=function(s){var o=typeof s=="string",u=n.call(arguments,1),a=this;return o?this.each(function(){var n,r=e.data(this,i);if(s==="instance")return a=r,!1;if(!r)return e.error("cannot call methods on "+t+" prior to initialization; "+"attempted to call method '"+s+"'");if(!e.isFunction(r[s])||s.charAt(0)==="_")return e.error("no such method '"+s+"' for "+t+" widget instance");n=r[s].apply(r,u);if(n!==r&&n!==undefined)return a=n&&n.jquery?a.pushStack(n.get()):n,!1}):(u.length&&(s=e.widget.extend.apply(null,[s].concat(u))),this.each(function(){var t=e.data(this,i);t?(t.option(s||{}),t._init&&t._init()):e.data(this,i,new r(s,this))})),a}},e.Widget=function(){},e.Widget._childConstructors=[],e.Widget.prototype={widgetName:"widget",widgetEventPrefix:"",defaultElement:"<div>",options:{disabled:!1,create:null},_createWidget:function(n,r){r=e(r||this.defaultElement||this)[0],this.element=e(r),this.uuid=t++,this.eventNamespace="."+this.widgetName+this.uuid,this.bindings=e(),this.hoverable=e(),this.focusable=e(),r!==this&&(e.data(r,this.widgetFullName,this),this._on(!0,this.element,{remove:function(e){e.target===r&&this.destroy()}}),this.document=e(r.style?r.ownerDocument:r.document||r),this.window=e(this.document[0].defaultView||this.document[0].parentWindow)),this.options=e.widget.extend({},this.options,this._getCreateOptions(),n),this._create(),this._trigger("create",null,this._getCreateEventData()),this._init()},_getCreateOptions:e.noop,_getCreateEventData:e.noop,_create:e.noop,_init:e.noop,destroy:function(){this._destroy(),this.element.unbind(this.eventNamespace).removeData(this.widgetFullName).removeData(e.camelCase(this.widgetFullName)),this.widget().unbind(this.eventNamespace).removeAttr("aria-disabled").removeClass(this.widgetFullName+"-disabled "+"ui-state-disabled"),this.bindings.unbind(this.eventNamespace),this.hoverable.removeClass("ui-state-hover"),this.focusable.removeClass("ui-state-focus")},_destroy:e.noop,widget:function(){return this.element},option:function(t,n){var r=t,i,s,o;if(arguments.length===0)return e.widget.extend({},this.options);if(typeof t=="string"){r={},i=t.split("."),t=i.shift();if(i.length){s=r[t]=e.widget.extend({},this.options[t]);for(o=0;o<i.length-1;o++)s[i[o]]=s[i[o]]||{},s=s[i[o]];t=i.pop();if(arguments.length===1)return s[t]===undefined?null:s[t];s[t]=n}else{if(arguments.length===1)return this.options[t]===undefined?null:this.options[t];r[t]=n}}return this._setOptions(r),this},_setOptions:function(e){var t;for(t in e)this._setOption(t,e[t]);return this},_setOption:function(e,t){return this.options[e]=t,e==="disabled"&&(this.widget().toggleClass(this.widgetFullName+"-disabled",!!t),t&&(this.hoverable.removeClass("ui-state-hover"),this.focusable.removeClass("ui-state-focus"))),this},enable:function(){return this._setOptions({disabled:!1})},disable:function(){return this._setOptions({disabled:!0})},_on:function(t,n,r){var i,s=this;typeof t!="boolean"&&(r=n,n=t,t=!1),r?(n=i=e(n),this.bindings=this.bindings.add(n)):(r=n,n=this.element,i=this.widget()),e.each(r,function(r,o){function u(){if(!t&&(s.options.disabled===!0||e(this).hasClass("ui-state-disabled")))return;return(typeof o=="string"?s[o]:o).apply(s,arguments)}typeof o!="string"&&(u.guid=o.guid=o.guid||u.guid||e.guid++);var a=r.match(/^([\w:-]*)\s*(.*)$/),f=a[1]+s.eventNamespace,l=a[2];l?i.delegate(l,f,u):n.bind(f,u)})},_off:function(t,n){n=(n||"").split(" ").join(this.eventNamespace+" ")+this.eventNamespace,t.unbind(n).undelegate(n),this.bindings=e(this.bindings.not(t).get()),this.focusable=e(this.focusable.not(t).get()),this.hoverable=e(this.hoverable.not(t).get())},_delay:function(e,t){function n(){return(typeof e=="string"?r[e]:e).apply(r,arguments)}var r=this;return setTimeout(n,t||0)},_hoverable:function(t){this.hoverable=this.hoverable.add(t),this._on(t,{mouseenter:function(t){e(t.currentTarget).addClass("ui-state-hover")},mouseleave:function(t){e(t.currentTarget).removeClass("ui-state-hover")}})},_focusable:function(t){this.focusable=this.focusable.add(t),this._on(t,{focusin:function(t){e(t.currentTarget).addClass("ui-state-focus")},focusout:function(t){e(t.currentTarget).removeClass("ui-state-focus")}})},_trigger:function(t,n,r){var i,s,o=this.options[t];r=r||{},n=e.Event(n),n.type=(t===this.widgetEventPrefix?t:this.widgetEventPrefix+t).toLowerCase(),n.target=this.element[0],s=n.originalEvent;if(s)for(i in s)i in n||(n[i]=s[i]);return this.element.trigger(n,r),!(e.isFunction(o)&&o.apply(this.element[0],[n].concat(r))===!1||n.isDefaultPrevented())}},e.each({show:"fadeIn",hide:"fadeOut"},function(t,n){e.Widget.prototype["_"+t]=function(r,i,s){typeof i=="string"&&(i={effect:i});var o,u=i?i===!0||typeof i=="number"?n:i.effect||n:t;i=i||{},typeof i=="number"&&(i={duration:i}),o=!e.isEmptyObject(i),i.complete=s,i.delay&&r.delay(i.delay),o&&e.effects&&e.effects.effect[u]?r[t](i):u!==t&&r[u]?r[u](i.duration,i.easing,s):r.queue(function(n){e(this)[t](),s&&s.call(r[0]),n()})}}),e.widget});