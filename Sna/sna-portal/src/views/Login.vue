<template>
  <div class="app flex-row align-items-center">
    <div class="container">
      <div class="row justify-content-center">
         <img src="../../static/img/logo.png" height="120"/>
      </div>
      <div class="row justify-content-center">

        <div class="col-md-8">
          <div class="card-group mb-0">
            <div class="card p-4">
              <div class="card-body">
                <h1>Login</h1>
                <p class="text-muted">Sign In to your account</p>
                <div class="input-group mb-3">
                  <span class="input-group-addon"><i class="icon-user"></i></span>
                  <input v-model="email" type="email" class="form-control" placeholder="Email">
                </div>
                <div class="input-group mb-4">
                  <span class="input-group-addon"><i class="icon-lock"></i></span>
                  <input v-model="password" type="password" class="form-control" placeholder="Password">
                </div>
                <div class="row">
                  <div class="col-6">
                    <button type="button" @click="login" class="btn btn-primary px-4">Login</button>
                  </div>
                  <div class="col-6 text-right">
                    <button type="button" @click = "resetPassword"class="btn btn-link px-0">Forgot password?</button>
                  </div>
                </div>
              </div>
            </div>
            <div class="card text-white bg-primary py-5 d-md-down-none" style="width:44%">
              <div class="card-body text-center">
                <div>

                  <h2>Sign up</h2>
                  <button type="button" @click="register" class="btn btn-primary active mt-3">Register Now!</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { excecute } from '../api'
export default {
  name: 'login',
  data () {
    return {
      email: '',
      password: ''
    }
  },
  methods: {
    login () {
      if (this.email === '' || this.password === '') {
        alert('Enter email and passoword')
        return
      }
      var data = new FormData()
      data.append('function', 'login')
      data.append('email', this.email)
      data.append('password', this.password)
      excecute(data).then((res) => {
        // TODO
        if (res.data.success) {
          sessionStorage.setItem('email', res.data.data.email)
          sessionStorage.setItem('company', res.data.data.company)
          sessionStorage.setItem('user', res.data.data.user)
          sessionStorage.setItem('type', res.data.data.type)
          this.$router.push('/dashboard')
        } else {
          alert(res.data.message)
        }
      }).catch((e) => {
        // TODO
      })
    },
    register () {
      this.$router.push('/register')
    },
    resetPassword () {
      this.$router.push('/forgot-password')
    }
  }
}
</script>
