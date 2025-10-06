// Servicio para exportar reportes de tareas (CSV / PDF)
// Usa la instancia centralizada de HTTP con responseType 'blob' para manejar archivos binarios
import http from './http';

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

  const config = {
    responseType: 'blob' // Importante: Axios debe tratar la respuesta como blob
  };

  try {
    const response = await http.get(url, config);

    // Intentar extraer filename del header
    let filename = 'tasks_report.'+format;
    const disp = response.headers['content-disposition'];
    if(disp){
      const m = /filename="?([^";]+)"?/i.exec(disp);
      if(m) filename = m[1];
    }

    return { blob: response.data, filename };

  } catch (error) {
    let msg = 'Error al exportar';

    // Manejar errores cuando la respuesta es un blob que contiene JSON de error
    if(error.response?.data instanceof Blob) {
      try {
        const text = await error.response.data.text();
        const errorData = JSON.parse(text);
        msg = errorData.error || errorData.message || msg;
      } catch(_) {
        // Si no se puede parsear como JSON, mantener mensaje genérico
      }
    } else if(error.response?.data) {
      // Si la respuesta no es blob pero tiene datos de error
      msg = error.response.data.error || error.response.data.message || msg;
    }

    throw new Error(msg);
  }
}
