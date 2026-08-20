import api from './api'

async function create(programId, payload) {
  const response = await api.post(`/programs/${programId}/applications`, payload)
  return response.data
}

export default {
  create,
}
