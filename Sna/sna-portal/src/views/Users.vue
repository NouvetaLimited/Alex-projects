<template>
  <div class="animated fadeIn">

    <div class="card" >
      <div class ="card-header">
        <i class="fa fa-align-justify"></i> Users
      </div>
      <div class="card-body">
        <table class="table table-striped">
          <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Account Balance</th>
            <th>Status</th>
            <th>Sender IDs</th>
            <th>Actions</th>
            <th>Date Created</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="user in users">
            <td>{{ user.user }}</td>
            <td>{{ user.email }}</td>
            <td>{{ user.accountBalance }}</td>
            <td>{{ user.status }}</td>
            <td>{{ formatSenderIds(user.senderIds) }}</td>
            <td>
              <div class="row">
                <div class="col-lg-6 col-md-6">
                  <input class="form-control" type="text" v-model="user.senderId"/>
                </div>
                <div class="col-lg-6 col-md-6">
                    <div class="btn-toolbar btn-group">
                        <input type="button" class="btn btn-success" value="Add" @click="addSenderId(user)"/>
                        <input type="button" class="btn btn-danger" value="Delete" @click="deleteSenderId(user)"/>
                        <input type="button" class="btn btn-info" value="Approve" @click="approveUser(user)"/>
                        <input type="button" class="btn btn-secondary" value="TopUP" @click="topUP(user)"/>
                    </div>
                </div>
              </div>
            </td>
              <td>{{ user.dateCreated }}</td>

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
      users: []
    }
  },
  methods: {
    getUsers () {
      var data = new FormData()
      data.append('function', 'users')
      excecute(data).then((res) => {
        this.users = res.data.data
      }).catch((e) => {
        // TODO
      })
    },
    addSenderId (user) {
      if (user.senderId === '') {
        alert('Enter Sender ID')
        return
      }
      var data = new FormData()
      data.append('function', 'addSenderId')
      data.append('company', user.company)
      data.append('senderId', user.senderId)
      excecute(data).then((res) => {
        this.getUsers()
      }).catch((e) => {
        // TODO
      })
    },
    deleteSenderId (user) {
      if (user.senderId === '') {
        alert('Enter Sender ID')
        return
      }
      var data = new FormData()
      data.append('function', 'deleteSenderId')
      data.append('userId', user.email)
      data.append('senderId', user.senderId)
      excecute(data).then((res) => {
        this.getUsers()
      }).catch((e) => {
        // TODO
      })
    },
    approveUser (user) {
      var data = new FormData()
      data.append('function', 'updateStatus')
      data.append('userId', user.email)
      excecute(data).then((res) => {
        this.getUsers()
      }).catch((e) => {
        // TODO
        this.getUsers()
      })
    },
    topUP (user) {
      if (user.senderId === '') {
        alert('Enter amount')
        return
      }
      var data = new FormData()
      data.append('function', 'accountTop')
      data.append('email', user.email)
      data.append('amount', user.senderId)
      data.append('company', sessionStorage.getItem('company'))
      excecute(data).then((res) => {
        this.getUsers()
      }).catch((e) => {
        // TODO
        this.getUsers()
      })
    },
    formatSenderIds (senderIds) {
      // return senderIds.join()
      var userSenderIds = []
      for (let senderId of senderIds) {
        userSenderIds.push(senderId.senderId)
      }
      return userSenderIds.join(',')
    }
  },
  computed: {
  },
  created () {
    this.getUsers()
  }
}
</script>
