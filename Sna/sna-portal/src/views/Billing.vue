<template>
  <div class="animated fadeIn">
    <div class="row">
      <div class="col-lg-6">
        <b-button type="submit" size="sm" variant="primary"  @click="generatePDF('topUp')"><i class="fa fa-download"></i> Download</b-button>
        <b-card header="<i class='fa fa-align-justify'></i> TopUp Transactions">
          Total TopUp Transaction {{ transactions.length }}
          <div class="card" >
            <div class="card-body">
              <table class="table table-striped">
                <thead>
                <tr>
                  <th>Date</th>
                  <th>Transaction Type</th>
                  <th>Amount</th>
                  <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="recipient in transactions">
                  <td>{{ recipient.date }}</td>
                  <td>{{ recipient.transactionType }}</td>
                  <td>KES.{{ recipient.amount }}</td>
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

      <div class="col-lg-6"><br/><br/>
        <b-card header="<i class='fa fa-align-justify'></i> Top Up Account from here">


          <div class="card" >
            <div class="card-body">
              <div class="col-lg-6 col-md-6">
                Enter Mpesa Mobile number
                <input class="form-control" type="text" v-model="phone"/><br/>
                Enter amount to TopUp
                <input class="form-control" type="text" v-model="amount"/><br/>

                <b-button type="submit" size="sm" variant="primary" @click="payMe()"><i class="fa fa-paypal"></i> Pay</b-button>
              </div>

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
      name: 'Billing',
      data () {
        return {
          transactions: [],
          amount: '',
          phone: ''
        }
      },
      methods: {
        getTransactions (transactionType) {
          var data = new FormData()
          data.append('email', sessionStorage.getItem('email'))
          data.append('company', sessionStorage.getItem('company'))
          data.append('transactionType', transactionType)
          data.append('function', 'transactions')
          excecute(data).then((res) => {
            this.transactions = res.data.data
          }).catch((e) => {
            // TODO
          })
        },
        payMe () {
          if (this.phone === '' || this.amount === '') {
            alert('Enter amount and Mpesa mobile number')
            return
          }
          var data = new FormData()
          data.append('email', sessionStorage.getItem('email'))
          data.append('function', 'mpesaPush')
          data.append('amount', this.amount)
          data.append('phoneNumber', this.phone)
          data.append('company', sessionStorage.getItem('company'))
          excecute(data).then((res) => {
            alert('Notification sent to your phone')
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
        this.getTransactions('topUp')
      }
    }
</script>

