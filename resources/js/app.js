import './bootstrap';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import Chart from 'chart.js/auto';
import { createIcons, LayoutDashboard, Package, ArrowDownUp, Repeat2, Shapes, Building2, Users, KeyRound, ScanSearch, ChartNoAxesCombined, X, Menu, Bell, LogOut } from 'lucide';

createIcons({icons:{LayoutDashboard,Package,ArrowDownUp,Repeat2,Shapes,Building2,Users,KeyRound,ScanSearch,ChartNoAxesCombined,X,Menu,Bell,LogOut}});
const root=document.documentElement;
document.querySelector('[data-sidebar-open]')?.addEventListener('click',()=>root.classList.add('sidebar-open'));
document.querySelectorAll('[data-sidebar-close]').forEach(el=>el.addEventListener('click',()=>root.classList.remove('sidebar-open')));
document.addEventListener('keydown',e=>{if(e.key==='Escape')root.classList.remove('sidebar-open')});
document.querySelectorAll('a[href]').forEach(link=>link.addEventListener('click',e=>{const url=new URL(link.href,location.href);if(!e.defaultPrevented&&url.origin===location.origin&&!link.target&&url.href!==location.href&&!url.hash)document.body.classList.add('is-navigating')}));
window.addEventListener('pageshow',()=>document.body.classList.remove('is-navigating'));
const canvas=document.querySelector('#movementChart');
if(canvas){const gradient=canvas.getContext('2d').createLinearGradient(0,0,0,280);gradient.addColorStop(0,'rgba(246,201,14,.24)');gradient.addColorStop(1,'rgba(246,201,14,0)');new Chart(canvas,{type:'line',data:{labels:['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'],datasets:[{label:'Entradas',data:[12,19,8,15,12,18,22],borderColor:'#e4b900',backgroundColor:gradient,fill:true,tension:.4,borderWidth:2.5,pointRadius:0,pointHoverRadius:5},{label:'Salidas',data:[8,12,14,10,17,13,18],borderColor:'#24241f',backgroundColor:'transparent',tension:.4,borderWidth:2,pointRadius:0,pointHoverRadius:5}]},options:{responsive:true,maintainAspectRatio:false,interaction:{intersect:false,mode:'index'},plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:7,padding:22,font:{size:11}}}},scales:{x:{grid:{display:false},border:{display:false}},y:{beginAtZero:true,border:{display:false},grid:{color:'#efefe8'}}}}})}
document.querySelectorAll('form').forEach(form=>form.addEventListener('submit',()=>{if(!form.checkValidity())return;const button=form.querySelector('[data-loading-button],button[type="submit"],button:not([type])');if(button){button.disabled=true;button.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Procesando...'}}));
document.querySelector('[data-whatsapp]')?.addEventListener('click',()=>window.open(`https://wa.me/?text=${encodeURIComponent(`Reporte de inventario LIUVA - ${document.title}`)}`,'_blank','noopener'));
document.querySelectorAll('.alert').forEach((alert,i)=>setTimeout(()=>{alert.style.cssText+='opacity:0;transform:translateY(-6px);transition:.3s';setTimeout(()=>alert.remove(),320)},5200+i*300));
