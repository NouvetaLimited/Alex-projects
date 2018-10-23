<template>
  <div class="animated fadeIn">
    <b-card>
      <div slot="header">
        <strong>Send Airtime</strong>
      </div>
      <b-button type="submit" size="sm" variant="primary" onclick="location.href='http://sna.node.nouveta.tech/api/templates/airtime'"><i class="fa fa-download" ></i> Download Template</b-button>
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
                      label="Enter Amount # phone separated by commas ','"
                      description="">
                <textarea class="form-control" v-model="phoneNumbers"></textarea>
              </b-form-fieldset>

            </div>
            <card-bar-chart-example class="chart-wrapper px-3" style="height:10px;" height="10"/>
          </b-card>
         <b>Amount#07XXXXXXX,07XXXXXXXX,07XXXXXXXX</b>
        </div><!--/.col-->
      </div><!--/.row-->

      <div slot="footer">
        <b-button @click="submit" type="submit" size="sm" variant="primary"><i class="fa fa-dot-circle-o"></i> Submit</b-button>
        <b-button type="reset" size="sm" variant="danger"><i class="fa fa-ban"></i> Reset</b-button>
      </div>
    </b-card>

    <div class="card" v-if="recipients.length>0" >
      <div class ="card-header">
        <i class="fa fa-align-justify"></i> Send SMS to <button @click="sendAirtime" type="submit" class="btn float-right btn-primary btn-sm" size="sm" variant="primary"><i class="fa fa-dot-circle-o"></i> Send Airtime</button>
      </div>
      <div class="card-body">
        <table class="table table-striped">
          <thead>
          <tr>
            <th>Member ID</th>
            <th>Name</th>
            <th>Phone No.</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="recipient in recipients">
            <td>{{ recipient.memberId }}</td>
            <td>{{ recipient.name }}</td>
            <td>{{ recipient.phoneNumber }}</td>
            <td>{{ recipient.amount }}</td>
            <td>
              <span class="badge badge-success">{{ recipient.status }}</span>
            </td>
            <td>
              <b-button @click="deleteUploads(recipient.phoneNumber)" type="delete" size="sm" variant="delete"><i class="fa fa-ban"></i>Delete</b-button>
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
  name: 'airtime',
  data () {
    return {
      excel: null,
      recipients: [],
      bolean: true,
      phoneNumbers: ''
    }
  },
  methods: {
    selectFile (e) {
      // TODO
      this.excel = e.target.files[0]
    },
    submit () {
      //
      if (this.excel === null) {
        if (this.phoneNumbers === '') {
          alert('Upload file or Enter Phone number')
          return
        } else {
          this.bolean = false
        }
      } else {
        this.bolean = true
      }
      var data = new FormData()
      data.append('excel', this.excel)
      data.append('import', this.bolean)
      data.append('phoneNumbers', this.phoneNumbers)
      data.append('transactionType', 'airtime')
      data.append('email', sessionStorage.getItem('email'))
      data.append('company', sessionStorage.getItem('company'))
      upload(data).then((res) => {
        // TODO
        this.recipients = res.data.data
      }).catch((e) => {
        // TODO
      })
    },
    sendAirtime () {
      // TODO
      var data = new FormData()
      data.append('function', 'sendAirtime')
      data.append('email', sessionStorage.getItem('email'))
      data.append('company', sessionStorage.getItem('company'))
      excecute(data).then((res) => {
        // TODO
        this.getUploads()
        alert('Processing airtime request')
      }).catch((e) => {
        // TODO
      })
    },
    getUploads () {
      var data = new FormData()
      data.append('function', 'uploads')
      data.append('transactionType', 'airtime')
      data.append('email', sessionStorage.getItem('email'))
      data.append('company', sessionStorage.getItem('company'))
      excecute(data).then((res) => {
        this.recipients = res.data.data
      }).catch((e) => {
        // TODO
      })
    }
  },
  deleteUploads (phoneNumber) {
    var data = new FormData()
    data.append('function', 'deleteUploads')
    data.append('transactionType', 'airtime')
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
  created () {
    this.getUploads()
  }
}
</script>
