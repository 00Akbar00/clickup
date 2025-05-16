<template>

<div class="auth-wrapper">

<!-- Top bar -->

<div class="top-bar d-flex justify-content-between align-items-center px-4 pt-3">

<img src="@/assets/mylogo.svg" alt="Logo" class="logo" />

<div class="d-flex align-items-center gap-2">

<span class="top-bar-text text-muted small">

{{ isSignup ? 'Already have an account?' : "Don't have an account?" }}

</span>

<button class="btn btn-outline-primary top-btn" @click="isSignup = !isSignup">

{{ isSignup ? 'Log in' : 'Sign up' }}

</button>

</div>

</div>



<div class="wave-bg"></div>



<div class="auth-container d-flex align-items-center justify-content-center">

<div class="auth-card card p-4 shadow rounded-4">

<div class="text-center mb-3">

<h3>

{{

resetMode ? 'Reset Your Password'

: isForgotPassword ? 'Forgot Password'

: isSignup ? 'Create Account'

: 'Welcome back!'

}}

</h3>

</div>



<!-- Login/Signup Form -->

<form @submit.prevent="handleAuth" v-if="!isForgotPassword && !resetMode">

<div v-if="isSignup" class="mb-3">

<label class="form-label">Username</label>

<input v-model="username" class="form-control" type="text" required />

</div>



<div class="mb-3">

<label class="form-label">Email</label>

<input v-model="email" class="form-control" type="email" required />

</div>



<div class="mb-3 position-relative">

<label class="form-label">Password</label>

<input :type="showPassword ? 'text' : 'password'" v-model="password" class="form-control" required />

<i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'" @click="togglePassword"

style="position:absolute; right:10px; top:38px; cursor:pointer;"></i>

</div>



<div v-if="isSignup" class="mb-3">

<label class="form-label">Confirm Password</label>

<input :type="showPassword ? 'text' : 'password'" v-model="confirmPassword" class="form-control" required />

<div v-if="confirmPassword && confirmPassword !== password" class="text-danger small mt-1">

Passwords do not match

</div>

</div>



<div v-if="!isSignup" class="mb-2 text-end">

<a href="#" class="text-primary small" @click.prevent="router.push({ name: 'forgot-password' })">

Forgot Password?

</a>

</div>



<button type="submit" class="btn btn-primary w-100" :disabled="isSignup && password !== confirmPassword">

{{ isSignup ? 'Sign Up' : 'Log In' }}

</button>

</form>



<!-- Forgot Password Form -->

<form v-else-if="isForgotPassword && !resetMode" @submit.prevent="handleResetRequest">

<div class="mb-3">

<label class="form-label">Enter your email to reset password</label>

<input v-model="email" class="form-control" type="email" required />

</div>



<button type="submit" class="btn btn-primary w-100">Send Reset Link</button>



<div class="text-center mt-2">

<a href="#" @click.prevent="router.push({ name: 'auth' })" class="text-secondary small">Back to Login</a>

</div>

</form>



<!-- Reset Password Form -->

<form v-else-if="resetMode" @submit.prevent="handlePasswordReset">

<div class="mb-3">

<label class="form-label">New Password</label>

<input :type="showPassword ? 'text' : 'password'" v-model="password" class="form-control" required />

</div>



<div class="mb-3">

<label class="form-label">Confirm New Password</label>

<input :type="showPassword ? 'text' : 'password'" v-model="confirmPassword" class="form-control" required />

<div v-if="confirmPassword && confirmPassword !== password" class="text-danger small mt-1">

Passwords do not match

</div>

</div>



<button type="submit" class="btn btn-primary w-100" :disabled="password !== confirmPassword">

Create New Password

</button>



<div class="text-center mt-2">

<a href="#" @click.prevent="router.push({ name: 'auth' })" class="text-secondary small">Back to Login</a>

</div>

</form>

</div>

</div>

</div>

</template>



<script setup>

import { ref, onMounted, watch } from 'vue'

import { useRouter, useRoute } from 'vue-router'

import axios from 'axios'



const router = useRouter()

const route = useRoute()



const isSignup = ref(false)

const isForgotPassword = ref(false)

const resetMode = ref(false)

const resetToken = ref(null)

const email = ref('')

const password = ref('')

const confirmPassword = ref('')

const username = ref('')

const showPassword = ref(false)



onMounted(() => setModeFromRoute())

watch(route, () => setModeFromRoute())



function setModeFromRoute() {

const name = route.name

if (name === 'forgot-password') {

isForgotPassword.value = true

resetMode.value = false

isSignup.value = false

} else if (name === 'reset-password') {

resetToken.value = route.params.token

resetMode.value = true

isForgotPassword.value = false

isSignup.value = false

} else {

resetMode.value = false

isForgotPassword.value = false

}

}



const togglePassword = () => {

showPassword.value = !showPassword.value

}



const handleAuth = async () => {

try {

if (isSignup.value) {

const response = await axios.post('http://localhost/api/signup', {

full_name: username.value,

email: email.value,

password: password.value,

password_confirmation: confirmPassword.value

})

localStorage.setItem('authUser', JSON.stringify(response.data.user || {}))

router.push({ name: 'create-workspace' })

} else {

const response = await axios.post('http://localhost/api/login', {

email: email.value,

password: password.value

})
console.log(response)
localStorage.setItem('authUser', JSON.stringify(response.data.user || {}))

router.push({ name: 'home' })

}

} catch (error) {

alert(error.response?.data?.message || 'Authentication failed')

console.error(error)
}

}



const handleResetRequest = async () => {

try {

const res = await axios.post('http://localhost/api/forgot-password', {

email: email.value

})

alert('Reset link sent to your email')

} catch (err) {

alert('Error sending reset link')

console.error(err)

}

}



const handlePasswordReset = async () => {

if (password.value !== confirmPassword.value) return



try {

const res = await axios.post(`http://localhost/api/reset-password/${resetToken.value}`, {

password: password.value,

password_confirmation: confirmPassword.value

})



alert('Password reset successful! You can now log in.')

router.push({ name: 'auth' })

} catch (err) {

alert('Failed to reset password.')

console.error(err)

}

}

</script>






<style scoped>



.auth-wrapper {



height: 100vh;



position: relative;



background: #f9f9fb;



display: flex;



flex-direction: column;



justify-content: flex-end;



}





.wave-bg {



background: linear-gradient(to top left, #E046AC, #7257FB);



height: 30vh;



width: 100%;



clip-path: ellipse(100% 100% at 50% 100%);



position: absolute;



bottom: 0;



left: 0;



z-index: 0;



}





.wave-bg::after {



content: "";



position: absolute;



top: 0;



left: 0;



height: 100%;



width: 100%;



background-image: radial-gradient(#ffffff33 1.5px, transparent 1.5px);



background-size: 22px 22px;



opacity: 0.9; /* Adjust dot visibility */



pointer-events: none;



}





.auth-container {



position: relative;



z-index: 1;



flex: 1;



padding-bottom: 5vh;



}





.auth-card {



width: 100%;



max-width: 420px;



background: white;



border-radius: 1.5rem;



}



.btn{



background-color: #553ED0;



padding: 10px 16px;





}



.btn:hover{



background-color: #553ED0;



}





.form-label {



font-size: 0.85rem; /* smaller heading */



font-weight: 500;



text-align: left;



display: block;



margin-bottom: 4px;



color: #444;



}





.top-bar {



position: absolute;



top: 0;



width: 100%;



z-index: 2;



}





.logo {



height: 40px;



}





.top-btn {



border-color: #553ED0;



color: white;



font-weight: 500;



border-radius: 0.5rem;



padding: 6px 16px;



}



.top-btn:hover {



background-color: #553ED0;



color: white;



}





</style>