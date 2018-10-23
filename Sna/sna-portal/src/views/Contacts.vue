<template>
  <div class="animated fadeIn">

    <div class="card" >
      <div class ="card-block">
        <b-button @click="submitSMS" type="submit" size="sm" variant="primary"><i class="fa fa-dot-circle-o"></i> Send SMS</b-button>
      </div>
      <div class ="card-header">
        <i class="fa fa-align-justify"></i> Users
      </div>
      <div class="card-body">
        <table class="table table-striped">
          <thead>
          <tr>
            <th>Name</th>
            <th>Phone Number</th>
            <th>Date Created</th>
            <th>Actions</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="user in users">
            <td>{{ user.name }}</td>
            <td>{{ user.phoneNumber }}</td>
            <td>{{ user.dateCreated }}</td>
            <td>
              <b-button @click="deleteContact(user.phoneNumber)" type="delete" size="sm" variant="delete"><i class="fa fa-ban"></i>Delete</b-button>
            </td>

          </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import { excecute } from '../api'
export default {
  name: 'airtime',
  data () {
    return {
      users: [],
      newNumber: ''
    }
  },
  methods: {
    getUsers () {
      var data = new FormData()
      data.append('function', 'customers')
      data.append('email', sessionStorage.getItem('email'))
      data.append('company', sessionStorage.getItem('company'))
      excecute(data).then((res) => {
        this.users = res.data.data
      }).catch((e) => {
        // TODO
      })
    },
    deleteContact (phoneNumber) {
      var data = new FormData()
      data.append('function', 'deleteContact')
      data.append('phoneNumber', phoneNumber)
      data.append('email', sessionStorage.getItem('email'))
      data.append('company', sessionStorage.getItem('company'))
      excecute(data).then((res) => {
        this.getUsers()
        this.recipients = res.data.data
        this.messageToSend = res.data.message
      }).catch((e) => {
        // TODO
      })
    },
    submitSMS () {
      var data = new FormData()
      data.append('function', 'submitSMS')
      data.append('email', sessionStorage.getItem('email'))
      data.append('company', sessionStorage.getItem('company'))
      excecute(data).then((res) => {
        this.$router.push('/Sms')
      }).catch((e) => {
        // TODO
      })
    }
  },
  computed: {
  },
  created () {
    this.getUsers()
  }
}
</script>
