import api from './api'

async function list(params = {}) {
  const response = await api.get('/notifications', { params })
  return response.data
}

async function show(notificationId) {
  const response = await api.get(`/notifications/${notificationId}`)
  return response.data
}

async function markAsRead(notificationId) {
  const response = await api.patch(`/notifications/${notificationId}/read`)
  return response.data
}

async function markAllAsRead() {
  const response = await api.patch('/notifications/read-all')
  return response.data
}

export default {
  list,
  show,
  markAsRead,
  markAllAsRead,
}
