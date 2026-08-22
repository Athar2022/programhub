import api from './api'

async function list(params = {}) {
  const response = await api.get('/programs', { params })
  return response.data
}

async function organizationList(organizationId, params = {}) {
  const response = await api.get(`/organizations/${organizationId}/programs`, { params })
  return response.data
}

async function show(programId) {
  const response = await api.get(`/programs/${programId}`)
  return response.data
}

async function create(organizationId, payload) {
  const response = await api.post(`/organizations/${organizationId}/programs`, payload)
  return response.data
}

async function update(programId, payload) {
  const response = await api.patch(`/programs/${programId}`, payload)
  return response.data
}

async function remove(programId) {
  const response = await api.delete(`/programs/${programId}`)
  return response.data
}

export default {
  list,
  organizationList,
  show,
  create,
  update,
  remove,
}
