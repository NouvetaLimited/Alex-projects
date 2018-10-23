<template>
  <div class="animated fadeIn">
    <b-card>
      <div slot="header">
        <strong>Send SMS</strong>
      </div>
        <b-button type="submit" size="sm" variant="primary" onclick="location.href='http://sna.node.nouveta.tech/api/templates/sms'"><i class="fa fa-download"></i> Download Template</b-button>
        <div class="row">
            <div class="col-sm-6 col-lg-3">
                <b-card class="bg-primary" :no-block="true">
                    <div class="card-body pb-0">
                        <b-form-fieldset
                                label="Select Excel or CSV file"
                                description="">
                            <input  ref="excel" type="file" class="form-control" @change="selectFile">
                        </b-form-fieldset>

                    </div>
                    <card-line1-chart-example class="chart-wrapper px-3" style="height:10px;" height="10"/>
                </b-card>
            </div><!--/.col-->
            <div class="col-sm-6 col-lg-3">
            </div><!--/.col-->

            <div class="col-sm-6 col-lg-3">
                <b-card class="bg-danger" :no-block="true">
                    <div class="card-body pb-0">

                        <b-form-fieldset
                                label="Enter phone separated by commas ','"
                                description="">
                            <textarea class="form-control" v-model="phoneNumbers"></textarea>
                        </b-form-fieldset>

                    </div>
                    <card-bar-chart-example class="chart-wrapper px-3" style="height:10px;" height="10"/>
                </b-card>
            </div><!--/.col-->
        </div><!--/.row-->



      <b-form-fieldset
              label="Type your message"
              description="">
        <textarea class="form-control" v-model="message"></textarea>
       <b> {{ messageToSend }}</b>
      </b-form-fieldset>


      <b-form-fieldset
              label="Select Sender ID"
              description="">
        <select class="form-control" v-model="sender_id">
          <option v-for="senderId in senderIds" :value="senderId.senderId" :key="senderId.id">{{ senderId.senderId }}</option>
        </select>

      </b-form-fieldset>
      <div slot="footer">
        <b-button @click="submit" type="submit" size="sm" variant="primary"><i class="fa fa-dot-circle-o"></i> Submit</b-button>
        <b-button type="reset" size="sm" variant="danger"><i class="fa fa-ban"></i> Reset</b-button>
      </div>
    </b-card>

    <div class="card" v-if="recipients.length>0">
      <div class ="card-header">
        <i class="fa fa-align-justify"></i> Send SMS to <button  @click="sendSms" type="submit" class="btn float-right btn-primary btn-sm" size="sm" variant="primary" v-if="userType==='admin' || userType==='superAdmin'"  ><i class="fa fa-dot-circle-o"></i> Send SMS</button>
      </div>
      <div class="card-body">
        <table class="table table-striped">
        <thead>
        <tr>
          <th>Member ID</th>
          <th>Name</th>
          <th>Phone No.</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="recipient in recipients">
          <td>{{ recipient.memberId }}</td>
          <td>{{ recipient.name }}</td>
          <td>{{ recipient.phoneNumber }}</td>
          <td>
            <span class="badge badge-success">{{ recipient.status }}</span>
          </td>
            <td>
                <b-button @click="deleteUpload(recipient.phoneNumber)" type="delete" size="sm" variant="delete"><i class="fa fa-ban"></i>Delete</b-button>
            </td>
        </tr>
        </tbody>
      </table>
      </div>
    </div>
  </div>
</template>

<script>
import { upload, excecute } from '../api'
export default {
  name: 'sms',
  data () {
    return {
      excel: null,
      message: '',
      messageToSend: '',
      sender_id: -1,
      recipients: [],
      senderIds: [],
      phoneNumbers: '',
      bolean: true,
      phone: ''
    }
  },
  methods: {
    selectFile (e) {
      // TODO
      this.excel = e.target.files[0]
    },
    submit () {
      //
      if (this.message === '') {
        alert('Fill in message')
        return
      } if (this.excel === null) {
        if (this.phoneNumbers === '') {
          alert('upload an excel or Enter phone Number separated by ","')
          return
        } else {
          this.bolean = false
        }
      } else {
        this.bolean = true
      }
      var data = new FormData()
      data.append('import', this.bolean)
      data.append('excel', this.excel)
      data.append('phoneNumbers', this.phoneNumbers)
      data.append('transactionType', 'sms')
      data.append('email', sessionStorage.getItem('email'))
      data.append('company', sessionStorage.getItem('company'))
      data.append('message', this.message)
      upload(data).then((res) => {
        // TODO
        this.recipients = res.data.data
      }).catch((e) => {
        // TODO
      })
    },
    sendSms () {
      // TODO
      if (this.message === '' || this.sender_id === -1) {
        alert('Fill in message and select Sender ID')
        return
      }
      var data = new FormData()
      data.append('function', 'sendSMS')
      data.append('message', this.message)
      data.append('email', sessionStorage.getItem('email'))
      data.append('company', sessionStorage.getItem('company'))
      data.append('senderId', this.sender_id)
      excecute(data).then((res) => {
        // TODO
        this.getUploads()
        alert('Messages sent')
      }).catch((e) => {
        // TODO
      })
    },
    getUploads () {
      var data = new FormData()
      data.append('function', 'uploads')
      data.append('transactionType', 'sms')
      data.append('email', sessionStorage.getItem('email'))
      data.append('company', sessionStorage.getItem('company'))
      excecute(data).then((res) => {
        this.recipients = res.data.data
        this.messageToSend = res.data.message
      }).catch((e) => {
        // TODO
      })
    },
    deleteUpload (phoneNumber) {
      var data = new FormData()
      data.append('function', 'deleteUploads')
      data.append('transactionType', 'sms')
      data.append('phoneNumber', phoneNumber)
      data.append('email', sessionStorage.getItem('email'))
      data.append('company', sessionStorage.getItem('company'))
      excecute(data).then((res) => {
        this.getUploads()
        this.recipients = res.data.data
        this.messageToSend = res.data.message
      }).catch((e) => {
        // TODO
      })
    },
    getsenderIds () {
      var data = new FormData()
      data.append('function', 'senderIds')
      data.append('company', sessionStorage.getItem('company'))
      excecute(data).then((res) => {
        // TODO
        this.senderIds = res.data.data
      })
    }},
  computed: {
    userType () {
      return sessionStorage.getItem('type')
    }
  },
  created () {
    this.getUploads()
    this.getsenderIds()
  }
}
</script>
