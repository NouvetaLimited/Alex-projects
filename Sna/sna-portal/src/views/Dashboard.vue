<template>
  <div class="animated fadeIn">
    <div class="row">
      <div class="col-sm-6 col-lg-3">
        <b-card class="bg-primary" :no-block="true">
          <div class="card-body pb-0">
            <p>Total SMS Transaction</p>
            <h4 class="mb-0"> {{ smsTransactionsTotal }}</h4>
          </div>
          <card-line1-chart-example class="chart-wrapper px-3" style="height:10px;" height="10"/>
        </b-card>
      </div><!--/.col-->
      <div class="col-sm-6 col-lg-3">
      </div><!--/.col-->


      <div class="col-sm-6 col-lg-3">
        <b-card class="bg-danger" :no-block="true">
          <div class="card-body pb-0">
            <p>Total Airtime Transaction</p>
            <h4 class="mb-0"> {{ airtimeTransactionsTotal }}</h4>
          </div>
          <card-bar-chart-example class="chart-wrapper px-3" style="height:10px;" height="10"/>
        </b-card>
      </div><!--/.col-->
    </div><!--/.row-->

    <!---->
    <div class="row">
      <div class="col-lg-6">
        <b-button type="submit" size="sm" variant="primary"  @click="generatePDF('sms')"><i class="fa fa-download"></i> Download</b-button>
        <b-card header="<i class='fa fa-align-justify'></i> SMS Transactions">
          <div class="card" >
            <div class="card-body">
              <table class="table table-striped">
                <thead>
                <tr>
                  <th>Member ID</th>
                  <th>Name</th>
                  <th>Phone No.</th>
                  <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="recipient in smsTransactions">
                  <td>{{ recipient.memberId }}</td>
                  <td>{{ recipient.name }}</td>
                  <td>{{ recipient.phoneNumber }}</td>
                  <td>
                    <span class="badge badge-success">{{ recipient.status }}</span>
                  </td>
                </tr>
                </tbody>
              </table>
            </div>
          </div>

        </b-card>
      </div><!--/.col-->

      <div class="col-lg-6">
        <b-button type="submit" size="sm" variant="primary" @click="generatePDF('airtime')"><i class="fa fa-download"></i> Download</b-button>
        <b-card header="<i class='fa fa-align-justify'></i> Airtime Transactions">
          <div class="card" >
            <div class="card-body">
              <table class="table table-striped">
                <thead>
                <tr>
                  <th>Member ID</th>
                  <th>Name</th>
                  <th>Phone No.</th>
                  <th>Amount</th>
                  <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="recipient in airtimeTransactions">
                  <td>{{ recipient.memberId }}</td>
                  <td>{{ recipient.name }}</td>
                  <td>{{ recipient.phoneNumber }}</td>
                  <td>{{ recipient.amount }}</td>
                  <td>
                    <span class="badge badge-success">{{ recipient.status }}</span>
                  </td>
                </tr>
                </tbody>
              </table>
            </div>
          </div>

        </b-card>
      </div><!--/.col-->
    </div><!--/.row-->

  </div>

</template>

<script>
import { excecute } from '../api'
export default {
  name: 'dashboard',
  data () {
    return {
      smsTransactions: [],
      airtimeTransactions: [],
      smsTransactionsTotal: '',
      airtimeTransactionsTotal: ''
    }
  },
  methods: {
    getTransactions (transactionType) {
      var data = new FormData()
      data.append('email', sessionStorage.getItem('email'))
      data.append('transactionType', transactionType)
      data.append('company', sessionStorage.getItem('company'))
      data.append('function', 'transactions')
      excecute(data).then((res) => {
        if (transactionType === 'sms') {
          this.smsTransactions = res.data.data
          this.smsTransactionsTotal = res.data.message
        }
        if (transactionType === 'airtime') {
          this.airtimeTransactions = res.data.data
          this.airtimeTransactionsTotal = res.data.message
        }
      }).catch((e) => {
      // TODO
      })
    },
    generatePDF (transactionType) {
      var data = new FormData()
      data.append('email', sessionStorage.getItem('email'))
      data.append('company', sessionStorage.getItem('company'))
      data.append('transactionType', transactionType)
      data.append('function', 'generatePDF')
      excecute(data).then((res) => {
        alert('Generate PDF')
      }).catch((e) => {
      // TODO
      })
    }
  },
  created () {
    this.getTransactions('sms')
    this.getTransactions('airtime')
  }
}
</script>

