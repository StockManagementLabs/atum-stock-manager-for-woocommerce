!function(t){function e(n){if(o[n])return o[n].exports;var i=o[n]={i:n,l:!1,exports:{}};return t[n].call(i.exports,i,i.exports,e),i.l=!0,i.exports}var o={};e.m=t,e.c=o,e.d=function(t,o,n){e.o(t,o)||Object.defineProperty(t,o,{configurable:!1,enumerable:!0,get:n})},e.n=function(t){var o=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(o,"a",o),o},e.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},e.p="",e(e.s=67)}({0:function(t,e){t.exports=Swal},2:function(t,e,o){"use strict";var n=function(){function t(t,e){void 0===e&&(e={}),this.varName=t,this.defaults=e,this.settings={};var o=void 0!==window[t]?window[t]:{};Object.assign(this.settings,e,o)}return t.prototype.get=function(t){if(void 0!==this.settings[t])return this.settings[t]},t.prototype.getAll=function(){return this.settings},t.prototype.delete=function(t){this.settings.hasOwnProperty(t)&&delete this.settings[t]},t}();e.a=n},67:function(t,e,o){"use strict";Object.defineProperty(e,"__esModule",{value:!0});var n=o(68),i=o(2);jQuery(function(t){window.$=t;var e=new i.a("atumMarketingPopupVars");new n.a(e)})},68:function(t,e,o){"use strict";var n=o(0),i=o.n(n),s=function(){function t(t){this.settings=t,this.getPopupInfo()}return t.prototype.getPopupInfo=function(){var t=this;$.ajax({url:window.ajaxurl,dataType:"json",method:"post",data:{action:"atum_get_marketing_popup_info",token:this.settings.get("nonce")},success:function(e){if(!0===e.success){var o=e.data,n=o.description.text_color?"color:"+o.description.text_color+";":"",s=o.description.text_size?"font-size:"+o.description.text_size+";":"",r=o.description.text_align?"text-align:"+o.description.text_align+";":"",a=o.description.padding?"padding:"+o.description.padding+";":"",c='<p data-transient-key="'+o.transient_key+'" style="'+(n+s+r+a)+'">'+o.description.text+"</p>",u="",p="",l="",d=o.title.text_color?"color:"+o.title.text_color+";":"",g=o.title.text_size?"font-size:"+o.title.text_size+";":"",f=o.title.text_align?"text-align:"+o.title.text_align+";":"",x=void 0,h="",_=o.hoverButtons||"",v=o.images.top_left,b='<img class="mp-logo" src="'+o.images.logo+'">',y=o.footerNotice.bg_color?' style="background-color:'+o.footerNotice.bg_color+';"':"",k=o.footerNotice.text?'<div class="footer-notice"'+y+">"+o.footerNotice.text+"</div>":"";o.version&&Object.keys(o.version).length&&(u=o.version.text_color?"color:"+o.version.text_color+";":"",p=o.version.background?"background:"+o.version.background+";":"",l='<span class="version" style="'+(p+u)+'">'+o.version.text+"</span>"),x='<h1 style="'+(d+g+f)+'"><span>'+(o.title.text+l)+"</span></h1>",o.buttons&&o.buttons.length&&(_&&$(_).appendTo("body"),o.buttons.forEach(function(t){h+='<button data-url="'+t.url+'" class="'+t.class+' popup-button" style="'+t.css+'">'+t.text+"</button>"})),i.a.fire({width:520,padding:null,customClass:{container:"marketing-popup"},background:o.background,showCloseButton:!0,showConfirmButton:!1,html:b+x+c+h+k,imageUrl:v}),$(".popup-button").on("click",function(t){t.preventDefault(),window.open($(t.currentTarget).data("url"),"_blank")}),$(".marketing-popup .swal2-close").on("click",function(){t.hideMarketingPopup($(".swal2-content p").data("transient-key"))})}}})},t.prototype.hideMarketingPopup=function(t){$.ajax({url:window.ajaxurl,dataType:"json",method:"post",data:{action:"atum_hide_marketing_popup",token:this.settings.get("nonce"),transientKey:t}})},t}();e.a=s}});