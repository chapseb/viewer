// MooTools: the javascript framework.
// Load this file's selection again by visiting: http://mootools.net/more/f0c595201cc3451c3f7e949f2b8b84b6 
// Or build this file again with packager using: packager build More/Element.Event.Pseudos More/Element.Position More/Fx.Elements More/Fx.Move More/Fx.Reveal More/Fx.Slide More/Drag More/Drag.Move More/Slider More/Tips
/*
---
copyrights:
  - [MooTools](http://mootools.net)

licenses:
  - [MIT License](http://mootools.net/license.txt)
...
*/
MooTools.More={version:"1.4.0.1",build:"a4244edf2aa97ac8a196fc96082dd35af1abab87"};(function(){Events.Pseudos=function(h,e,f){var d="_monitorEvents:";var c=function(i){return{store:i.store?function(j,k){i.store(d+j,k);
}:function(j,k){(i._monitorEvents||(i._monitorEvents={}))[j]=k;},retrieve:i.retrieve?function(j,k){return i.retrieve(d+j,k);}:function(j,k){if(!i._monitorEvents){return k;
}return i._monitorEvents[j]||k;}};};var g=function(k){if(k.indexOf(":")==-1||!h){return null;}var j=Slick.parse(k).expressions[0][0],p=j.pseudos,i=p.length,o=[];
while(i--){var n=p[i].key,m=h[n];if(m!=null){o.push({event:j.tag,value:p[i].value,pseudo:n,original:k,listener:m});}}return o.length?o:null;};return{addEvent:function(m,p,j){var n=g(m);
if(!n){return e.call(this,m,p,j);}var k=c(this),r=k.retrieve(m,[]),i=n[0].event,l=Array.slice(arguments,2),o=p,q=this;n.each(function(s){var t=s.listener,u=o;
if(t==false){i+=":"+s.pseudo+"("+s.value+")";}else{o=function(){t.call(q,s,u,arguments,o);};}});r.include({type:i,event:p,monitor:o});k.store(m,r);if(m!=i){e.apply(this,[m,p].concat(l));
}return e.apply(this,[i,o].concat(l));},removeEvent:function(m,l){var k=g(m);if(!k){return f.call(this,m,l);}var n=c(this),j=n.retrieve(m);if(!j){return this;
}var i=Array.slice(arguments,2);f.apply(this,[m,l].concat(i));j.each(function(o,p){if(!l||o.event==l){f.apply(this,[o.type,o.monitor].concat(i));}delete j[p];
},this);n.store(m,j);return this;}};};var b={once:function(e,f,d,c){f.apply(this,d);this.removeEvent(e.event,c).removeEvent(e.original,f);},throttle:function(d,e,c){if(!e._throttled){e.apply(this,c);
e._throttled=setTimeout(function(){e._throttled=false;},d.value||250);}},pause:function(d,e,c){clearTimeout(e._pause);e._pause=e.delay(d.value||250,this,c);
}};Events.definePseudo=function(c,d){b[c]=d;return this;};Events.lookupPseudo=function(c){return b[c];};var a=Events.prototype;Events.implement(Events.Pseudos(b,a.addEvent,a.removeEvent));
["Request","Fx"].each(function(c){if(this[c]){this[c].implement(Events.prototype);}});})();(function(){var d={relay:false},c=["once","throttle","pause"],b=c.length;
while(b--){d[c[b]]=Events.lookupPseudo(c[b]);}DOMEvent.definePseudo=function(e,f){d[e]=f;return this;};var a=Element.prototype;[Element,Window,Document].invoke("implement",Events.Pseudos(d,a.addEvent,a.removeEvent));
})();(function(){var b=function(e,d){var f=[];Object.each(d,function(g){Object.each(g,function(h){e.each(function(i){f.push(i+"-"+h+(i=="border"?"-width":""));
});});});return f;};var c=function(f,e){var d=0;Object.each(e,function(h,g){if(g.test(f)){d=d+h.toInt();}});return d;};var a=function(d){return !!(!d||d.offsetHeight||d.offsetWidth);
};Element.implement({measure:function(h){if(a(this)){return h.call(this);}var g=this.getParent(),e=[];while(!a(g)&&g!=document.body){e.push(g.expose());
g=g.getParent();}var f=this.expose(),d=h.call(this);f();e.each(function(i){i();});return d;},expose:function(){if(this.getStyle("display")!="none"){return function(){};
}var d=this.style.cssText;this.setStyles({display:"block",position:"absolute",visibility:"hidden"});return function(){this.style.cssText=d;}.bind(this);
},getDimensions:function(d){d=Object.merge({computeSize:false},d);var i={x:0,y:0};var h=function(j,e){return(e.computeSize)?j.getComputedSize(e):j.getSize();
};var f=this.getParent("body");if(f&&this.getStyle("display")=="none"){i=this.measure(function(){return h(this,d);});}else{if(f){try{i=h(this,d);}catch(g){}}}return Object.append(i,(i.x||i.x===0)?{width:i.x,height:i.y}:{x:i.width,y:i.height});
},getComputedSize:function(d){d=Object.merge({styles:["padding","border"],planes:{height:["top","bottom"],width:["left","right"]},mode:"both"},d);var g={},e={width:0,height:0},f;
if(d.mode=="vertical"){delete e.width;delete d.planes.width;}else{if(d.mode=="horizontal"){delete e.height;delete d.planes.height;}}b(d.styles,d.planes).each(function(h){g[h]=this.getStyle(h).toInt();
},this);Object.each(d.planes,function(i,h){var k=h.capitalize(),j=this.getStyle(h);if(j=="auto"&&!f){f=this.getDimensions();}j=g[h]=(j=="auto")?f[h]:j.toInt();
e["total"+k]=j;i.each(function(m){var l=c(m,g);e["computed"+m.capitalize()]=l;e["total"+k]+=l;});},this);return Object.append(e,g);}});})();(function(b){var a=Element.Position={options:{relativeTo:document.body,position:{x:"center",y:"center"},offset:{x:0,y:0}},getOptions:function(d,c){c=Object.merge({},a.options,c);
a.setPositionOption(c);a.setEdgeOption(c);a.setOffsetOption(d,c);a.setDimensionsOption(d,c);return c;},setPositionOption:function(c){c.position=a.getCoordinateFromValue(c.position);
},setEdgeOption:function(d){var c=a.getCoordinateFromValue(d.edge);d.edge=c?c:(d.position.x=="center"&&d.position.y=="center")?{x:"center",y:"center"}:{x:"left",y:"top"};
},setOffsetOption:function(f,d){var c={x:0,y:0},g=f.measure(function(){return document.id(this.getOffsetParent());}),e=g.getScroll();if(!g||g==f.getDocument().body){return;
}c=g.measure(function(){var i=this.getPosition();if(this.getStyle("position")=="fixed"){var h=window.getScroll();i.x+=h.x;i.y+=h.y;}return i;});d.offset={parentPositioned:g!=document.id(d.relativeTo),x:d.offset.x-c.x+e.x,y:d.offset.y-c.y+e.y};
},setDimensionsOption:function(d,c){c.dimensions=d.getDimensions({computeSize:true,styles:["padding","border","margin"]});},getPosition:function(e,d){var c={};
d=a.getOptions(e,d);var f=document.id(d.relativeTo)||document.body;a.setPositionCoordinates(d,c,f);if(d.edge){a.toEdge(c,d);}var g=d.offset;c.left=((c.x>=0||g.parentPositioned||d.allowNegative)?c.x:0).toInt();
c.top=((c.y>=0||g.parentPositioned||d.allowNegative)?c.y:0).toInt();a.toMinMax(c,d);if(d.relFixedPosition||f.getStyle("position")=="fixed"){a.toRelFixedPosition(f,c);
}if(d.ignoreScroll){a.toIgnoreScroll(f,c);}if(d.ignoreMargins){a.toIgnoreMargins(c,d);}c.left=Math.ceil(c.left);c.top=Math.ceil(c.top);delete c.x;delete c.y;
return c;},setPositionCoordinates:function(k,g,d){var f=k.offset.y,h=k.offset.x,e=(d==document.body)?window.getScroll():d.getPosition(),j=e.y,c=e.x,i=window.getSize();
switch(k.position.x){case"left":g.x=c+h;break;case"right":g.x=c+h+d.offsetWidth;break;default:g.x=c+((d==document.body?i.x:d.offsetWidth)/2)+h;break;}switch(k.position.y){case"top":g.y=j+f;
break;case"bottom":g.y=j+f+d.offsetHeight;break;default:g.y=j+((d==document.body?i.y:d.offsetHeight)/2)+f;break;}},toMinMax:function(c,d){var f={left:"x",top:"y"},e;
["minimum","maximum"].each(function(g){["left","top"].each(function(h){e=d[g]?d[g][f[h]]:null;if(e!=null&&((g=="minimum")?c[h]<e:c[h]>e)){c[h]=e;}});});
},toRelFixedPosition:function(e,c){var d=window.getScroll();c.top+=d.y;c.left+=d.x;},toIgnoreScroll:function(e,d){var c=e.getScroll();d.top-=c.y;d.left-=c.x;
},toIgnoreMargins:function(c,d){c.left+=d.edge.x=="right"?d.dimensions["margin-right"]:(d.edge.x!="center"?-d.dimensions["margin-left"]:-d.dimensions["margin-left"]+((d.dimensions["margin-right"]+d.dimensions["margin-left"])/2));
c.top+=d.edge.y=="bottom"?d.dimensions["margin-bottom"]:(d.edge.y!="center"?-d.dimensions["margin-top"]:-d.dimensions["margin-top"]+((d.dimensions["margin-bottom"]+d.dimensions["margin-top"])/2));
},toEdge:function(c,d){var e={},g=d.dimensions,f=d.edge;switch(f.x){case"left":e.x=0;break;case"right":e.x=-g.x-g.computedRight-g.computedLeft;break;default:e.x=-(Math.round(g.totalWidth/2));
break;}switch(f.y){case"top":e.y=0;break;case"bottom":e.y=-g.y-g.computedTop-g.computedBottom;break;default:e.y=-(Math.round(g.totalHeight/2));break;}c.x+=e.x;
c.y+=e.y;},getCoordinateFromValue:function(c){if(typeOf(c)!="string"){return c;}c=c.toLowerCase();return{x:c.test("left")?"left":(c.test("right")?"right":"center"),y:c.test(/upper|top/)?"top":(c.test("bottom")?"bottom":"center")};
}};Element.implement({position:function(d){if(d&&(d.x!=null||d.y!=null)){return(b?b.apply(this,arguments):this);}var c=this.setStyle("position","absolute").calculatePosition(d);
return(d&&d.returnPos)?c:this.setStyles(c);},calculatePosition:function(c){return a.getPosition(this,c);}});})(Element.prototype.position);Fx.Elements=new Class({Extends:Fx.CSS,initialize:function(b,a){this.elements=this.subject=$$(b);
this.parent(a);},compute:function(g,h,j){var c={};for(var d in g){var a=g[d],e=h[d],f=c[d]={};for(var b in a){f[b]=this.parent(a[b],e[b],j);}}return c;
},set:function(b){for(var c in b){if(!this.elements[c]){continue;}var a=b[c];for(var d in a){this.render(this.elements[c],d,a[d],this.options.unit);}}return this;
},start:function(c){if(!this.check(c)){return this;}var h={},j={};for(var d in c){if(!this.elements[d]){continue;}var f=c[d],a=h[d]={},g=j[d]={};for(var b in f){var e=this.prepare(this.elements[d],b,f[b]);
a[b]=e.from;g[b]=e.to;}}return this.parent(h,j);}});Fx.Move=new Class({Extends:Fx.Morph,options:{relativeTo:document.body,position:"center",edge:false,offset:{x:0,y:0}},start:function(a){var b=this.element,c=b.getStyles("top","left");
if(c.top=="auto"||c.left=="auto"){b.setPosition(b.getPosition(b.getOffsetParent()));}return this.parent(b.position(Object.merge({},this.options,a,{returnPos:true})));
}});Element.Properties.move={set:function(a){this.get("move").cancel().setOptions(a);return this;},get:function(){var a=this.retrieve("move");if(!a){a=new Fx.Move(this,{link:"cancel"});
this.store("move",a);}return a;}};Element.implement({move:function(a){this.get("move").start(a);return this;}});Element.implement({isDisplayed:function(){return this.getStyle("display")!="none";
},isVisible:function(){var a=this.offsetWidth,b=this.offsetHeight;return(a==0&&b==0)?false:(a>0&&b>0)?true:this.style.display!="none";},toggle:function(){return this[this.isDisplayed()?"hide":"show"]();
},hide:function(){var b;try{b=this.getStyle("display");}catch(a){}if(b=="none"){return this;}return this.store("element:_originalDisplay",b||"").setStyle("display","none");
},show:function(a){if(!a&&this.isDisplayed()){return this;}a=a||this.retrieve("element:_originalDisplay")||"block";return this.setStyle("display",(a=="none")?"block":a);
},swapClass:function(a,b){return this.removeClass(a).addClass(b);}});Document.implement({clearSelection:function(){if(window.getSelection){var a=window.getSelection();
if(a&&a.removeAllRanges){a.removeAllRanges();}}else{if(document.selection&&document.selection.empty){try{document.selection.empty();}catch(b){}}}}});(function(){var a=function(d){var b=d.options.hideInputs;
if(window.OverText){var c=[null];OverText.each(function(e){c.include("."+e.options.labelClass);});if(c){b+=c.join(", ");}}return(b)?d.element.getElements(b):null;
};Fx.Reveal=new Class({Extends:Fx.Morph,options:{link:"cancel",styles:["padding","border","margin"],transitionOpacity:!Browser.ie6,mode:"vertical",display:function(){return this.element.get("tag")!="tr"?"block":"table-row";
},opacity:1,hideInputs:Browser.ie?"select, input, textarea, object, embed":null},dissolve:function(){if(!this.hiding&&!this.showing){if(this.element.getStyle("display")!="none"){this.hiding=true;
this.showing=false;this.hidden=true;this.cssText=this.element.style.cssText;var d=this.element.getComputedSize({styles:this.options.styles,mode:this.options.mode});
if(this.options.transitionOpacity){d.opacity=this.options.opacity;}var c={};Object.each(d,function(f,e){c[e]=[f,0];});this.element.setStyles({display:Function.from(this.options.display).call(this),overflow:"hidden"});
var b=a(this);if(b){b.setStyle("visibility","hidden");}this.$chain.unshift(function(){if(this.hidden){this.hiding=false;this.element.style.cssText=this.cssText;
this.element.setStyle("display","none");if(b){b.setStyle("visibility","visible");}}this.fireEvent("hide",this.element);this.callChain();}.bind(this));this.start(c);
}else{this.callChain.delay(10,this);this.fireEvent("complete",this.element);this.fireEvent("hide",this.element);}}else{if(this.options.link=="chain"){this.chain(this.dissolve.bind(this));
}else{if(this.options.link=="cancel"&&!this.hiding){this.cancel();this.dissolve();}}}return this;},reveal:function(){if(!this.showing&&!this.hiding){if(this.element.getStyle("display")=="none"){this.hiding=false;
this.showing=true;this.hidden=false;this.cssText=this.element.style.cssText;var d;this.element.measure(function(){d=this.element.getComputedSize({styles:this.options.styles,mode:this.options.mode});
}.bind(this));if(this.options.heightOverride!=null){d.height=this.options.heightOverride.toInt();}if(this.options.widthOverride!=null){d.width=this.options.widthOverride.toInt();
}if(this.options.transitionOpacity){this.element.setStyle("opacity",0);d.opacity=this.options.opacity;}var c={height:0,display:Function.from(this.options.display).call(this)};
Object.each(d,function(f,e){c[e]=0;});c.overflow="hidden";this.element.setStyles(c);var b=a(this);if(b){b.setStyle("visibility","hidden");}this.$chain.unshift(function(){this.element.style.cssText=this.cssText;
this.element.setStyle("display",Function.from(this.options.display).call(this));if(!this.hidden){this.showing=false;}if(b){b.setStyle("visibility","visible");
}this.callChain();this.fireEvent("show",this.element);}.bind(this));this.start(d);}else{this.callChain();this.fireEvent("complete",this.element);this.fireEvent("show",this.element);
}}else{if(this.options.link=="chain"){this.chain(this.reveal.bind(this));}else{if(this.options.link=="cancel"&&!this.showing){this.cancel();this.reveal();
}}}return this;},toggle:function(){if(this.element.getStyle("display")=="none"){this.reveal();}else{this.dissolve();}return this;},cancel:function(){this.parent.apply(this,arguments);
if(this.cssText!=null){this.element.style.cssText=this.cssText;}this.hiding=false;this.showing=false;return this;}});Element.Properties.reveal={set:function(b){this.get("reveal").cancel().setOptions(b);
return this;},get:function(){var b=this.retrieve("reveal");if(!b){b=new Fx.Reveal(this);this.store("reveal",b);}return b;}};Element.Properties.dissolve=Element.Properties.reveal;
Element.implement({reveal:function(b){this.get("reveal").setOptions(b).reveal();return this;},dissolve:function(b){this.get("reveal").setOptions(b).dissolve();
return this;},nix:function(b){var c=Array.link(arguments,{destroy:Type.isBoolean,options:Type.isObject});this.get("reveal").setOptions(b).dissolve().chain(function(){this[c.destroy?"destroy":"dispose"]();
}.bind(this));return this;},wink:function(){var c=Array.link(arguments,{duration:Type.isNumber,options:Type.isObject});var b=this.get("reveal").setOptions(c.options);
b.reveal().chain(function(){(function(){b.dissolve();}).delay(c.duration||2000);});}});})();Fx.Slide=new Class({Extends:Fx,options:{mode:"vertical",wrapper:false,hideOverflow:true,resetHeight:false},initialize:function(b,a){b=this.element=this.subject=document.id(b);
this.parent(a);a=this.options;var d=b.retrieve("wrapper"),c=b.getStyles("margin","position","overflow");if(a.hideOverflow){c=Object.append(c,{overflow:"hidden"});
}if(a.wrapper){d=document.id(a.wrapper).setStyles(c);}if(!d){d=new Element("div",{styles:c}).wraps(b);}b.store("wrapper",d).setStyle("margin",0);if(b.getStyle("overflow")=="visible"){b.setStyle("overflow","hidden");
}this.now=[];this.open=true;this.wrapper=d;this.addEvent("complete",function(){this.open=(d["offset"+this.layout.capitalize()]!=0);if(this.open&&this.options.resetHeight){d.setStyle("height","");
}},true);},vertical:function(){this.margin="margin-top";this.layout="height";this.offset=this.element.offsetHeight;},horizontal:function(){this.margin="margin-left";
this.layout="width";this.offset=this.element.offsetWidth;},set:function(a){this.element.setStyle(this.margin,a[0]);this.wrapper.setStyle(this.layout,a[1]);
return this;},compute:function(c,b,a){return[0,1].map(function(d){return Fx.compute(c[d],b[d],a);});},start:function(b,e){if(!this.check(b,e)){return this;
}this[e||this.options.mode]();var d=this.element.getStyle(this.margin).toInt(),c=this.wrapper.getStyle(this.layout).toInt(),a=[[d,c],[0,this.offset]],g=[[d,c],[-this.offset,0]],f;
switch(b){case"in":f=a;break;case"out":f=g;break;case"toggle":f=(c==0)?a:g;}return this.parent(f[0],f[1]);},slideIn:function(a){return this.start("in",a);
},slideOut:function(a){return this.start("out",a);},hide:function(a){this[a||this.options.mode]();this.open=false;return this.set([-this.offset,0]);},show:function(a){this[a||this.options.mode]();
this.open=true;return this.set([0,this.offset]);},toggle:function(a){return this.start("toggle",a);}});Element.Properties.slide={set:function(a){this.get("slide").cancel().setOptions(a);
return this;},get:function(){var a=this.retrieve("slide");if(!a){a=new Fx.Slide(this,{link:"cancel"});this.store("slide",a);}return a;}};Element.implement({slide:function(d,e){d=d||"toggle";
var b=this.get("slide"),a;switch(d){case"hide":b.hide(e);break;case"show":b.show(e);break;case"toggle":var c=this.retrieve("slide:flag",b.open);b[c?"slideOut":"slideIn"](e);
this.store("slide:flag",!c);a=true;break;default:b.start(d,e);}if(!a){this.eliminate("slide:flag");}return this;}});var Drag=new Class({Implements:[Events,Options],options:{snap:6,unit:"px",grid:false,style:true,limit:false,handle:false,invert:false,preventDefault:false,stopPropagation:false,modifiers:{x:"left",y:"top"}},initialize:function(){var b=Array.link(arguments,{options:Type.isObject,element:function(c){return c!=null;
}});this.element=document.id(b.element);this.document=this.element.getDocument();this.setOptions(b.options||{});var a=typeOf(this.options.handle);this.handles=((a=="array"||a=="collection")?$$(this.options.handle):document.id(this.options.handle))||this.element;
this.mouse={now:{},pos:{}};this.value={start:{},now:{}};this.selection=(Browser.ie)?"selectstart":"mousedown";if(Browser.ie&&!Drag.ondragstartFixed){document.ondragstart=Function.from(false);
Drag.ondragstartFixed=true;}this.bound={start:this.start.bind(this),check:this.check.bind(this),drag:this.drag.bind(this),stop:this.stop.bind(this),cancel:this.cancel.bind(this),eventStop:Function.from(false)};
this.attach();},attach:function(){this.handles.addEvent("mousedown",this.bound.start);return this;},detach:function(){this.handles.removeEvent("mousedown",this.bound.start);
return this;},start:function(a){var j=this.options;if(a.rightClick){return;}if(j.preventDefault){a.preventDefault();}if(j.stopPropagation){a.stopPropagation();
}this.mouse.start=a.page;this.fireEvent("beforeStart",this.element);var c=j.limit;this.limit={x:[],y:[]};var e,g;for(e in j.modifiers){if(!j.modifiers[e]){continue;
}var b=this.element.getStyle(j.modifiers[e]);if(b&&!b.match(/px$/)){if(!g){g=this.element.getCoordinates(this.element.getOffsetParent());}b=g[j.modifiers[e]];
}if(j.style){this.value.now[e]=(b||0).toInt();}else{this.value.now[e]=this.element[j.modifiers[e]];}if(j.invert){this.value.now[e]*=-1;}this.mouse.pos[e]=a.page[e]-this.value.now[e];
if(c&&c[e]){var d=2;while(d--){var f=c[e][d];if(f||f===0){this.limit[e][d]=(typeof f=="function")?f():f;}}}}if(typeOf(this.options.grid)=="number"){this.options.grid={x:this.options.grid,y:this.options.grid};
}var h={mousemove:this.bound.check,mouseup:this.bound.cancel};h[this.selection]=this.bound.eventStop;this.document.addEvents(h);},check:function(a){if(this.options.preventDefault){a.preventDefault();
}var b=Math.round(Math.sqrt(Math.pow(a.page.x-this.mouse.start.x,2)+Math.pow(a.page.y-this.mouse.start.y,2)));if(b>this.options.snap){this.cancel();this.document.addEvents({mousemove:this.bound.drag,mouseup:this.bound.stop});
this.fireEvent("start",[this.element,a]).fireEvent("snap",this.element);}},drag:function(b){var a=this.options;if(a.preventDefault){b.preventDefault();
}this.mouse.now=b.page;for(var c in a.modifiers){if(!a.modifiers[c]){continue;}this.value.now[c]=this.mouse.now[c]-this.mouse.pos[c];if(a.invert){this.value.now[c]*=-1;
}if(a.limit&&this.limit[c]){if((this.limit[c][1]||this.limit[c][1]===0)&&(this.value.now[c]>this.limit[c][1])){this.value.now[c]=this.limit[c][1];}else{if((this.limit[c][0]||this.limit[c][0]===0)&&(this.value.now[c]<this.limit[c][0])){this.value.now[c]=this.limit[c][0];
}}}if(a.grid[c]){this.value.now[c]-=((this.value.now[c]-(this.limit[c][0]||0))%a.grid[c]);}if(a.style){this.element.setStyle(a.modifiers[c],this.value.now[c]+a.unit);
}else{this.element[a.modifiers[c]]=this.value.now[c];}}this.fireEvent("drag",[this.element,b]);},cancel:function(a){this.document.removeEvents({mousemove:this.bound.check,mouseup:this.bound.cancel});
if(a){this.document.removeEvent(this.selection,this.bound.eventStop);this.fireEvent("cancel",this.element);}},stop:function(b){var a={mousemove:this.bound.drag,mouseup:this.bound.stop};
a[this.selection]=this.bound.eventStop;this.document.removeEvents(a);if(b){this.fireEvent("complete",[this.element,b]);}}});Element.implement({makeResizable:function(a){var b=new Drag(this,Object.merge({modifiers:{x:"width",y:"height"}},a));
this.store("resizer",b);return b.addEvent("drag",function(){this.fireEvent("resize",b);}.bind(this));}});Drag.Move=new Class({Extends:Drag,options:{droppables:[],container:false,precalculate:false,includeMargins:true,checkDroppables:true},initialize:function(b,a){this.parent(b,a);
b=this.element;this.droppables=$$(this.options.droppables);this.container=document.id(this.options.container);if(this.container&&typeOf(this.container)!="element"){this.container=document.id(this.container.getDocument().body);
}if(this.options.style){if(this.options.modifiers.x=="left"&&this.options.modifiers.y=="top"){var c=b.getOffsetParent(),d=b.getStyles("left","top");if(c&&(d.left=="auto"||d.top=="auto")){b.setPosition(b.getPosition(c));
}}if(b.getStyle("position")=="static"){b.setStyle("position","absolute");}}this.addEvent("start",this.checkDroppables,true);this.overed=null;},start:function(a){if(this.container){this.options.limit=this.calculateLimit();
}if(this.options.precalculate){this.positions=this.droppables.map(function(b){return b.getCoordinates();});}this.parent(a);},calculateLimit:function(){var j=this.element,e=this.container,d=document.id(j.getOffsetParent())||document.body,h=e.getCoordinates(d),c={},b={},k={},g={},m={};
["top","right","bottom","left"].each(function(q){c[q]=j.getStyle("margin-"+q).toInt();b[q]=j.getStyle("border-"+q).toInt();k[q]=e.getStyle("margin-"+q).toInt();
g[q]=e.getStyle("border-"+q).toInt();m[q]=d.getStyle("padding-"+q).toInt();},this);var f=j.offsetWidth+c.left+c.right,p=j.offsetHeight+c.top+c.bottom,i=0,l=0,o=h.right-g.right-f,a=h.bottom-g.bottom-p;
if(this.options.includeMargins){i+=c.left;l+=c.top;}else{o+=c.right;a+=c.bottom;}if(j.getStyle("position")=="relative"){var n=j.getCoordinates(d);n.left-=j.getStyle("left").toInt();
n.top-=j.getStyle("top").toInt();i-=n.left;l-=n.top;if(e.getStyle("position")!="relative"){i+=g.left;l+=g.top;}o+=c.left-n.left;a+=c.top-n.top;if(e!=d){i+=k.left+m.left;
l+=((Browser.ie6||Browser.ie7)?0:k.top)+m.top;}}else{i-=c.left;l-=c.top;if(e!=d){i+=h.left+g.left;l+=h.top+g.top;}}return{x:[i,o],y:[l,a]};},getDroppableCoordinates:function(c){var b=c.getCoordinates();
if(c.getStyle("position")=="fixed"){var a=window.getScroll();b.left+=a.x;b.right+=a.x;b.top+=a.y;b.bottom+=a.y;}return b;},checkDroppables:function(){var a=this.droppables.filter(function(d,c){d=this.positions?this.positions[c]:this.getDroppableCoordinates(d);
var b=this.mouse.now;return(b.x>d.left&&b.x<d.right&&b.y<d.bottom&&b.y>d.top);},this).getLast();if(this.overed!=a){if(this.overed){this.fireEvent("leave",[this.element,this.overed]);
}if(a){this.fireEvent("enter",[this.element,a]);}this.overed=a;}},drag:function(a){this.parent(a);if(this.options.checkDroppables&&this.droppables.length){this.checkDroppables();
}},stop:function(a){this.checkDroppables();this.fireEvent("drop",[this.element,this.overed,a]);this.overed=null;return this.parent(a);}});Element.implement({makeDraggable:function(a){var b=new Drag.Move(this,a);
this.store("dragger",b);return b;}});Class.Mutators.Binds=function(a){if(!this.prototype.initialize){this.implement("initialize",function(){});}return Array.from(a).concat(this.prototype.Binds||[]);
};Class.Mutators.initialize=function(a){return function(){Array.from(this.Binds).each(function(b){var c=this[b];if(c){this[b]=c.bind(this);}},this);return a.apply(this,arguments);
};};var Slider=new Class({Implements:[Events,Options],Binds:["clickedElement","draggedKnob","scrolledElement"],options:{onTick:function(a){this.setKnobPosition(a);
},initialStep:0,snap:false,offset:0,range:false,wheel:false,steps:100,mode:"horizontal"},initialize:function(f,a,e){this.setOptions(e);e=this.options;this.element=document.id(f);
a=this.knob=document.id(a);this.previousChange=this.previousEnd=this.step=-1;var b={},d={x:false,y:false};switch(e.mode){case"vertical":this.axis="y";this.property="top";
this.offset="offsetHeight";break;case"horizontal":this.axis="x";this.property="left";this.offset="offsetWidth";}this.setSliderDimensions();this.setRange(e.range);
if(a.getStyle("position")=="static"){a.setStyle("position","relative");}a.setStyle(this.property,-e.offset);d[this.axis]=this.property;b[this.axis]=[-e.offset,this.full-e.offset];
var c={snap:0,limit:b,modifiers:d,onDrag:this.draggedKnob,onStart:this.draggedKnob,onBeforeStart:(function(){this.isDragging=true;}).bind(this),onCancel:function(){this.isDragging=false;
}.bind(this),onComplete:function(){this.isDragging=false;this.draggedKnob();this.end();}.bind(this)};if(e.snap){this.setSnap(c);}this.drag=new Drag(a,c);
this.attach();if(e.initialStep!=null){this.set(e.initialStep);}},attach:function(){this.element.addEvent("mousedown",this.clickedElement);if(this.options.wheel){this.element.addEvent("mousewheel",this.scrolledElement);
}this.drag.attach();return this;},detach:function(){this.element.removeEvent("mousedown",this.clickedElement).removeEvent("mousewheel",this.scrolledElement);
this.drag.detach();return this;},autosize:function(){this.setSliderDimensions().setKnobPosition(this.toPosition(this.step));this.drag.options.limit[this.axis]=[-this.options.offset,this.full-this.options.offset];
if(this.options.snap){this.setSnap();}return this;},setSnap:function(a){if(!a){a=this.drag.options;}a.grid=Math.ceil(this.stepWidth);a.limit[this.axis][1]=this.full;
return this;},setKnobPosition:function(a){if(this.options.snap){a=this.toPosition(this.step);}this.knob.setStyle(this.property,a);return this;},setSliderDimensions:function(){this.full=this.element.measure(function(){this.half=this.knob[this.offset]/2;
return this.element[this.offset]-this.knob[this.offset]+(this.options.offset*2);}.bind(this));return this;},set:function(a){if(!((this.range>0)^(a<this.min))){a=this.min;
}if(!((this.range>0)^(a>this.max))){a=this.max;}this.step=Math.round(a);return this.checkStep().fireEvent("tick",this.toPosition(this.step)).end();},setRange:function(a,b){this.min=Array.pick([a[0],0]);
this.max=Array.pick([a[1],this.options.steps]);this.range=this.max-this.min;this.steps=this.options.steps||this.full;this.stepSize=Math.abs(this.range)/this.steps;
this.stepWidth=this.stepSize*this.full/Math.abs(this.range);if(a){this.set(Array.pick([b,this.step]).floor(this.min).max(this.max));}return this;},clickedElement:function(c){if(this.isDragging||c.target==this.knob){return;
}var b=this.range<0?-1:1,a=c.page[this.axis]-this.element.getPosition()[this.axis]-this.half;a=a.limit(-this.options.offset,this.full-this.options.offset);
this.step=Math.round(this.min+b*this.toStep(a));this.checkStep().fireEvent("tick",a).end();},scrolledElement:function(a){var b=(this.options.mode=="horizontal")?(a.wheel<0):(a.wheel>0);
this.set(this.step+(b?-1:1)*this.stepSize);a.stop();},draggedKnob:function(){var b=this.range<0?-1:1,a=this.drag.value.now[this.axis];a=a.limit(-this.options.offset,this.full-this.options.offset);
this.step=Math.round(this.min+b*this.toStep(a));this.checkStep();},checkStep:function(){var a=this.step;if(this.previousChange!=a){this.previousChange=a;
this.fireEvent("change",a);}return this;},end:function(){var a=this.step;if(this.previousEnd!==a){this.previousEnd=a;this.fireEvent("complete",a+"");}return this;
},toStep:function(a){var b=(a+this.options.offset)*this.stepSize/this.full*this.steps;return this.options.steps?Math.round(b-=b%this.stepSize):b;},toPosition:function(a){return(this.full*Math.abs(this.min-a))/(this.steps*this.stepSize)-this.options.offset;
}});(function(){var a=function(c,b){return(c)?(typeOf(c)=="function"?c(b):b.get(c)):"";};this.Tips=new Class({Implements:[Events,Options],options:{onShow:function(){this.tip.setStyle("display","block");
},onHide:function(){this.tip.setStyle("display","none");},title:"title",text:function(b){return b.get("rel")||b.get("href");},showDelay:100,hideDelay:100,className:"tip-wrap",offset:{x:16,y:16},windowPadding:{x:0,y:0},fixed:false,waiAria:true},initialize:function(){var b=Array.link(arguments,{options:Type.isObject,elements:function(c){return c!=null;
}});this.setOptions(b.options);if(b.elements){this.attach(b.elements);}this.container=new Element("div",{"class":"tip"});if(this.options.id){this.container.set("id",this.options.id);
if(this.options.waiAria){this.attachWaiAria();}}},toElement:function(){if(this.tip){return this.tip;}this.tip=new Element("div",{"class":this.options.className,styles:{position:"absolute",top:0,left:0}}).adopt(new Element("div",{"class":"tip-top"}),this.container,new Element("div",{"class":"tip-bottom"}));
return this.tip;},attachWaiAria:function(){var b=this.options.id;this.container.set("role","tooltip");if(!this.waiAria){this.waiAria={show:function(c){if(b){c.set("aria-describedby",b);
}this.container.set("aria-hidden","false");},hide:function(c){if(b){c.erase("aria-describedby");}this.container.set("aria-hidden","true");}};}this.addEvents(this.waiAria);
},detachWaiAria:function(){if(this.waiAria){this.container.erase("role");this.container.erase("aria-hidden");this.removeEvents(this.waiAria);}},attach:function(b){$$(b).each(function(d){var f=a(this.options.title,d),e=a(this.options.text,d);
d.set("title","").store("tip:native",f).retrieve("tip:title",f);d.retrieve("tip:text",e);this.fireEvent("attach",[d]);var c=["enter","leave"];if(!this.options.fixed){c.push("move");
}c.each(function(h){var g=d.retrieve("tip:"+h);if(!g){g=function(i){this["element"+h.capitalize()].apply(this,[i,d]);}.bind(this);}d.store("tip:"+h,g).addEvent("mouse"+h,g);
},this);},this);return this;},detach:function(b){$$(b).each(function(d){["enter","leave","move"].each(function(e){d.removeEvent("mouse"+e,d.retrieve("tip:"+e)).eliminate("tip:"+e);
});this.fireEvent("detach",[d]);if(this.options.title=="title"){var c=d.retrieve("tip:native");if(c){d.set("title",c);}}},this);return this;},elementEnter:function(c,b){clearTimeout(this.timer);
this.timer=(function(){this.container.empty();["title","text"].each(function(e){var d=b.retrieve("tip:"+e);var f=this["_"+e+"Element"]=new Element("div",{"class":"tip-"+e}).inject(this.container);
if(d){this.fill(f,d);}},this);this.show(b);this.position((this.options.fixed)?{page:b.getPosition()}:c);}).delay(this.options.showDelay,this);},elementLeave:function(c,b){clearTimeout(this.timer);
this.timer=this.hide.delay(this.options.hideDelay,this,b);this.fireForParent(c,b);},setTitle:function(b){if(this._titleElement){this._titleElement.empty();
this.fill(this._titleElement,b);}return this;},setText:function(b){if(this._textElement){this._textElement.empty();this.fill(this._textElement,b);}return this;
},fireForParent:function(c,b){b=b.getParent();if(!b||b==document.body){return;}if(b.retrieve("tip:enter")){b.fireEvent("mouseenter",c);}else{this.fireForParent(c,b);
}},elementMove:function(c,b){this.position(c);},position:function(f){if(!this.tip){document.id(this);}var c=window.getSize(),b=window.getScroll(),g={x:this.tip.offsetWidth,y:this.tip.offsetHeight},d={x:"left",y:"top"},e={y:false,x2:false,y2:false,x:false},h={};
for(var i in d){h[d[i]]=f.page[i]+this.options.offset[i];if(h[d[i]]<0){e[i]=true;}if((h[d[i]]+g[i]-b[i])>c[i]-this.options.windowPadding[i]){h[d[i]]=f.page[i]-this.options.offset[i]-g[i];
e[i+"2"]=true;}}this.fireEvent("bound",e);this.tip.setStyles(h);},fill:function(b,c){if(typeof c=="string"){b.set("html",c);}else{b.adopt(c);}},show:function(b){if(!this.tip){document.id(this);
}if(!this.tip.getParent()){this.tip.inject(document.body);}this.fireEvent("show",[this.tip,b]);},hide:function(b){if(!this.tip){document.id(this);}this.fireEvent("hide",[this.tip,b]);
}});})();