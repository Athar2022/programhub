import api from './api'

async function list(params = {}) {
  const response = await api.get('/programs', { params })
  return response.data
}

async function show(programId) {
  const response = await api.get(`/programs/${programId}`)
  return response.data
}

export default {
  list,
  show,
}
