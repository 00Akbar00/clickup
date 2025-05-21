import axios from 'axios'

const instance = axios.create({
  baseURL: 'http://localhost/api',
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})

// Request interceptor
instance.interceptors.request.use(
  (config) => {
    // Try to get the token from multiple sources
    let token = localStorage.getItem('authToken')
    
    // If not found, try to get from authUser
    if (!token) {
      const authUser = localStorage.getItem('authUser')
      if (authUser) {
        try {
          const userData = JSON.parse(authUser)
          token = userData.token
        } catch (e) {
          console.error('Error parsing authUser:', e)
        }
      }
    }
    
    // If token exists, add it to the headers
    if (token) {
      // Ensure token has Bearer prefix
      config.headers.Authorization = token.startsWith('Bearer ') 
        ? token 
        : `Bearer ${token}`
    }
    
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
instance.interceptors.response.use(
  (response) => response,
  (error) => {
    // Handle Unauthorized errors
    if (error.response?.status === 401) {
      console.error('401 Unauthorized error:', {
        url: error.config?.url,
        method: error.config?.method,
        headers: error.config?.headers,
        response: error.response?.data
      })
      
      // Token expired or invalid - clear storage
      localStorage.removeItem('authToken')
      localStorage.removeItem('authUser')
      localStorage.removeItem('has_workspace')
      
      // Check if we're already on the auth page to prevent redirect loops
      const currentPath = window.location.pathname
      if (currentPath !== '/auth' && 
          !currentPath.includes('/forgot-password') && 
          !currentPath.includes('/reset-password')) {
        window.location.href = '/auth'
      }
    }
    
    // Customize error message if available from server
    if (error.response?.data?.message) {
      error.message = error.response.data.message
    }
    
    return Promise.reject(error)
  }
)

export default instance 