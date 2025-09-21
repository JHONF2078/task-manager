// Servicio de tareas: consumo de endpoints REST
// Ajusta las rutas si tu backend usa prefijos distintos.
import { httpGet, httpPost, httpPut, httpDelete } from './http';

function mapFilters(front){
  const mapped = {};
  if(front.search) mapped.q = front.search;
  if(front.status) mapped.status = front.status;
  if(front.priority) mapped.priority = front.priority;
  if(front.assignee) mapped.assignedTo = front.assignee; // id de usuario
  let from = front.dueFrom || '';
  let to = front.dueTo || '';
  if(from && to && from > to){ [from, to] = [to, from]; }
  if(from) mapped.dueFrom = from; // ahora sólo fecha pura (YYYY-MM-DD)
  if(to) mapped.dueTo = to;
  if(front.tags?.length) mapped.categories = front.tags.join(',');
  if(front.includeInactive) mapped.includeInactive = front.includeInactive ? '1':'0';
  if(front.page) mapped.page = front.page;
  if(front.limit) mapped.limit = front.limit;
  if(front.sortBy) mapped.sort = front.sortBy;
  if(front.sortDir) mapped.direction = front.sortDir;
  return mapped;
}

function buildQuery(params){
  const q = new URLSearchParams();
  Object.entries(params||{}).forEach(([k,v])=>{
    if(v===undefined || v===null || v==='') return;
    q.append(k, v);
  });
  const s = q.toString();
  return s ? `?${s}` : '';
}

function convertServerTask(t){
  if(!t) return t;
  return {
    id: t.id,
    title: t.title,
    description: t.description,
    status: t.status,
    priority: t.priority,
    dueDate: t.dueDate,
    categories: t.categories || [],
    tags: t.categories || [], // alias interno para UI (usábamos tags)
    assignee: t.assignedTo ? { id: t.assignedTo.id, email: t.assignedTo.email } : null,
    createdAt: t.createdAt,
    updatedAt: t.updatedAt,
    active: t.active,
    deletedAt: t.deletedAt,
  };
}

function preparePayload(task){
  // Front usa assignee / tags; backend espera assignedTo / categories
  const payload = { ...task };
  if('assignee' in payload){
    payload.assignedTo = payload.assignee || null;
    delete payload.assignee;
  }
  if(Array.isArray(payload.tags)){
    payload.categories = payload.tags;
    delete payload.tags;
  }
  return payload;
}

export async function fetchTasksApi(filters = {}) {
  const query = mapFilters(filters);
  const res = await httpGet(`/api/tasks${buildQuery(query)}`);
  // Puede devolver { meta, data } o array simple dependiendo de implementación futura
  if(res && Array.isArray(res.data)){
    return { meta: res.meta, data: res.data.map(convertServerTask) };
  }
  if(Array.isArray(res)) return { meta:null, data: res.map(convertServerTask) };
  return { meta:null, data: [] };
}

export async function createTaskApi(task){
  const payload = preparePayload(task);
  const created = await httpPost('/api/tasks', payload);
  return convertServerTask(created);
}

export async function updateTaskApi(id, task){
  const payload = preparePayload(task);
  const updated = await httpPut(`/api/tasks/${id}`, payload);
  return convertServerTask(updated);
}

export async function patchTaskApi(id, partial){
  const payload = preparePayload(partial);
  // reutilizamos httpPut? mejor httpPost con override; por simplicidad se añade método fetch directo si fuera necesario.
  // Si se implementa httpPatch se puede agregar; por ahora se usa PUT cuando patch no sea crítico.
  return await updateTaskApi(id, payload);
}

export async function deleteTaskApi(id){
  const deleted = await httpDelete(`/api/tasks/${id}`);
  return convertServerTask(deleted);
}
