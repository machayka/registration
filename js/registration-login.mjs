const n="imienazwisko@najmuje.eu";function o(){const e=document.querySelector('input#user[name="user"]');return e?(e.placeholder!==n&&(e.placeholder=n),!0):!1}if(!o()){const e=new MutationObserver(()=>{o()&&e.disconnect()});e.observe(document.body,{childList:!0,subtree:!0})}
//# sourceMappingURL=registration-login.mjs.map
