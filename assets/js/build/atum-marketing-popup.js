!function(t){var e={};function o(n){if(e[n])return e[n].exports;var i=e[n]={i:n,l:!1,exports:{}};return t[n].call(i.exports,i,i.exports,o),i.l=!0,i.exports}o.m=t,o.c=e,o.d=function(t,e,n){o.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:n})},o.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},o.t=function(t,e){if(1&e&&(t=o(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var n=Object.create(null);if(o.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var i in t)o.d(n,i,function(e){return t[e]}.bind(null,i));return n},o.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return o.d(e,"a",e),e},o.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},o.p="",o(o.s=87)}({0:function(t,e){t.exports=jQuery},1:function(t,e){t.exports=Swal},3:function(t,e,o){"use strict";var n=function(){function t(t,e){void 0===e&&(e={}),this.varName=t,this.defaults=e,this.settings={};var o=void 0!==window[t]?window[t]:{};Object.assign(this.settings,e,o)}return t.prototype.get=function(t){if(void 0!==this.settings[t])return this.settings[t]},t.prototype.getAll=function(){return this.settings},t.prototype.delete=function(t){this.settings.hasOwnProperty(t)&&delete this.settings[t]},t}();e.a=n},69:function(t,e,o){"use strict";(function(t){var n=o(1),i=o.n(n),r=function(){function e(t){this.settings=t,this.getPopupInfo()}return e.prototype.getPopupInfo=function(){var e=this;t.ajax({url:window.ajaxurl,dataType:"json",method:"post",data:{action:"atum_get_marketing_popup_info",security:this.settings.get("nonce")},success:function(o){if(!0===o.success){var n=o.data,r=n.description.text_color?"color:"+n.description.text_color+";":"",s=n.description.text_size?"font-size:"+n.description.text_size+";":"",a=n.description.text_align?"text-align:"+n.description.text_align+";":"",c=n.description.padding?"padding:"+n.description.padding+";":"",u='<p data-transient-key="'+n.transient_key+'" style="'+(r+s+a+c)+'">'+n.description.text+"</p>",l=n.title.text_color?"color:"+n.title.text_color+";":"",p=n.title.text_size?"font-size:"+n.title.text_size+";":"",d=n.title.text_align?"text-align:"+n.title.text_align+";":"",f=n.hoverButtons||"",g=n.images.top_left,y=n.footerNotice.bg_color?' style="background-color:'+n.footerNotice.bg_color+';"':"",h=n.footerNotice.text?'<div class="footer-notice"'+y+">"+n.footerNotice.text+"</div>":"",_='<img class="mp-logo" src="'+n.images.logo+'">',x="",b="",v="";n.images.hasOwnProperty("logo_css")&&n.images.logo_css&&(_=_.replace(">",' style="'+n.images.logo_css+'">')),n.version&&Object.keys(n.version).length&&(x=n.version.text_color?"color:"+n.version.text_color+";":"",b='<span class="version" style="'+((n.version.background?"background:"+n.version.background+";":"")+x)+'">'+n.version.text+"</span>");var m=n.title.text?'<h1 style="'+(l+p+d)+'"><span>'+(n.title.text+b)+"</span></h1>":"";n.buttons&&n.buttons.length&&(f&&t(f).appendTo("body"),n.buttons.forEach((function(t){v+='<button data-url="'+t.url+'" class="'+t.class+' popup-button" style="'+t.css+'">'+t.text+"</button>"}))),i.a.fire({width:520,padding:null,customClass:{popup:"marketing-popup"},background:n.background,showCloseButton:!0,showConfirmButton:!1,html:_+m+u+v+h,imageUrl:g,allowEscapeKey:!1,allowOutsideClick:!1,allowEnterKey:!1}).then((function(){e.hideMarketingPopup(t(".swal2-content p").data("transient-key"))})),t(".popup-button").click((function(e){e.preventDefault(),window.open(t(e.currentTarget).data("url"),"_blank")}))}}})},e.prototype.hideMarketingPopup=function(e){t.ajax({url:window.ajaxurl,dataType:"json",method:"post",data:{action:"atum_hide_marketing_popup",security:this.settings.get("nonce"),transientKey:e}})},e}();e.a=r}).call(this,o(0))},87:function(t,e,o){"use strict";o.r(e),function(t){var e=o(69),n=o(3);t((function(t){var o=new n.a("atumMarketingPopupVars");new e.a(o)}))}.call(this,o(0))}});