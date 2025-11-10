import axios from 'axios'

const http = axios.create({
  withCredentials: true, // bắt buộc cho Sanctum cookie
})
export default http
