<template>
    <div class="app flex-row align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <img src="../../static/img/logo.png" height="120"/>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card mx-4">
                        <div class="card-body p-4">
                            <h1>Register</h1>
                            <p class="text-muted">Create your account</p>
                            <div class="input-group mb-3">
                                <span class="input-group-addon"><i class="icon-user"></i></span>
                                <input type="text" class="form-control" placeholder="Name" v-model="user">
                            </div>

                            <div class="input-group mb-3">
                                <span class="input-group-addon">@</span>
                                <input type="text" class="form-control" placeholder="Email" v-model="email">
                            </div>

                            <div class="input-group mb-3">
                                <span class="input-group-addon"><i class="icon-lock"></i></span>
                                <input type="password" class="form-control" placeholder="Password" v-model="password">
                            </div>

                            <div class="input-group mb-4">
                                <span class="input-group-addon"><i class="icon-lock"></i></span>
                                <input type="password" class="form-control" placeholder="Repeat password" v-model="confirm_password">
                            </div>

                            <button type="button" @click="register" class="btn btn-block btn-success">Create Account</button>

                            <button type="button" @click="login" class="btn btn-block btn-success">Login Instead</button>
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
  name: 'register',
  data () {
    return {
      user: '',
      email: '',
      password: '',
      confirm_password: ''
    }
  },
  methods: {
    register () {
      // TODO
      if (this.user === '' || this.email === '' || this.password === '') {
        alert('Please fill  all Spaces')
        return
      }
      if (this.password === this.confirm_password) {
        var data = new FormData()
        data.append('function', 'register')
        data.append('email', this.email)
        data.append('company', this.email)
        data.append('password', this.password)
        data.append('user', this.user)
        data.append('userType', 'admin')
        excecute(data).then((res) => {
          if (res.data.success) {
            sessionStorage.setItem('email', res.data.data.email)
            sessionStorage.setItem('user', res.data.data.user)
            this.$router.push('/dashboard')
          } else {
            alert(res.data.message)
          }
        }).catch((e) => {

        })
      } else {
        alert('Password mismatch')
      }
    },
    login () {
      this.$router.push('/login')
    }
  }
}
</script>
