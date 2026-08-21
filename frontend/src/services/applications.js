import api from './api'

async function list(params = {}) {
  const response = await api.get('/applications', { params })
  return response.data
}

async function show(applicationId) {
  const response = await api.get(`/applications/${applicationId}`)
  return response.data
}

async function create(programId, payload) {
  const response = await api.post(`/programs/${programId}/applications`, payload)
  return response.data
}

export default {
  list,
  show,
  create,
}
