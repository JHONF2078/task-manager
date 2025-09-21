// Servicio para exportar reportes de tareas (CSV / PDF)
// Usa directamente fetch porque necesitamos blobs (no el wrapper que parsea JSON)
import { useAuthStore } from '../stores/authStore';

function buildQuery(params){
  const q = new URLSearchParams();
  Object.entries(params).forEach(([k,v])=>{
    if(v===undefined || v===null || v==='') return;
    q.append(k, v);
  });
  const s = q.toString();
  return s ? `?${s}` : '';
}

export async function exportTasksReport(format, filters){
  const auth = useAuthStore();
  // Mapear filtros internos a API
  const query = {
    format,
    from: filters.dueFrom || undefined,
    to: filters.dueTo || undefined,
    status: filters.status || undefined,
    priority: filters.priority || undefined,
    assigned: filters.assignee || undefined,
    sort: filters.sortBy || 'dueDate',
    direction: filters.sortDir || 'asc'
  };
  const url = '/api/reports/tasks'+buildQuery(query);
  const headers = new Headers();
  if(auth.token){ headers.set('Authorization', `${auth.tokenType||'Bearer'} ${auth.token}`); }
  const res = await fetch(url, { headers });
  if(!res.ok){
    let msg = 'Error al exportar';
    try { const j = await res.json(); msg = j.error || j.message || msg; } catch(_){ /* ignore */ }
    throw new Error(msg);
  }
  const blob = await res.blob();
  // Intentar extraer filename del header
  let filename = 'tasks_report.'+format;
  const disp = res.headers.get('Content-Disposition');
  if(disp){
    const m = /filename="?([^";]+)"?/i.exec(disp);
    if(m) filename = m[1];
  }
  return { blob, filename };
}

