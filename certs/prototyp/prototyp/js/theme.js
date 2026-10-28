(function(){const k='bajkuj-theme',r=document.documentElement;r.dataset.theme=localStorage.getItem(k)||'light';
document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('.theme').forEach(b=>{const s=()=>b.textContent=r.dataset.theme==='dark'?'☀':'☾';s();
b.onclick=()=>{r.dataset.theme=r.dataset.theme==='dark'?'light':'dark';localStorage.setItem(k,r.dataset.theme);s();};});});})();
