import api from './api'

async function list(applicationId) {
  const response = await api.get(`/applications/${applicationId}/documents`)
  return response.data
}

async function upload(applicationId, formData) {
  const response = await api.post(`/applications/${applicationId}/documents`, formData)
  return response.data
}

async function show(documentId) {
  const response = await api.get(`/documents/${documentId}`)
  return response.data
}

async function remove(documentId) {
  const response = await api.delete(`/documents/${documentId}`)
  return response.data
}

export default {
  list,
  upload,
  show,
  remove,
}
