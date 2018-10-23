<template>
    <div class="app flex-row align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card mx-4">
                        <div class="card-body p-4">
                            <p class="text-muted">RESET PASSWORD</p>

                            <div class="input-group mb-3">
                                <span class="input-group-addon">@</span>
                                <input type="text" class="form-control" placeholder="Email" v-model="email">
                            </div>

                            <button type="button" @click="resetPassword" class="btn btn-block btn-success">Reset Password</button>

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
  name: 'forgot-password',
  data () {
    return {
      email: ''
    }
  },
  methods: {
    resetPassword () {
      // TODO
      if (this.user === '' || this.email === '') {
        alert('Please enter email')
        return
      }
      var data = new FormData()
      data.append('function', 'forgotPassword')
      data.append('email', this.email)
      excecute(data).then((res) => {
        if (res.data.success) {
          alert('Please check your email')
          this.$router.push('/login')
        } else {
          alert(res.data.message)
        }
      }).catch((e) => {

      })
    },
    login () {
      this.$router.push('/login')
    }
  }
}
</script>
