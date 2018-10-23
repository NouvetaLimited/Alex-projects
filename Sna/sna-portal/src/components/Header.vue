<template>
  <header class="app-header navbar">
    <button class="navbar-toggler mobile-sidebar-toggler d-lg-none" type="button" @click="mobileSidebarToggle">&#9776;</button>
    <b-link class="navbar-brand" @click="home" to="#"></b-link>
    <button class="navbar-toggler sidebar-toggler d-md-down-none mr-auto" type="button" @click="sidebarMinimize">&#9776;</button>

    <b-nav is-nav-bar class="d-md-down-none">
      <b-button variant="primary" @click="getAccountBalance"><i class="icon-wallet"></i>&nbsp; {{ balance }}</b-button>
    </b-nav>

    <b-nav is-nav-bar class="ml-auto">

      <b-nav-item-dropdown right>
        <template slot="button-content">
          <span class="d-md-down-none">{{ getUser() }}</span>
        </template>
        <b-dropdown-item to="/login"><i class="fa fa-lock"></i> Logout </b-dropdown-item>
      </b-nav-item-dropdown>
    </b-nav>

  </header>
</template>
<script>
import { excecute } from '../api'
export default {
  name: 'header',
  data () {
    return {
      balance: 'KES 0.00'
    }
  },
  methods: {
    home () {
      this.$router.push('/dashboard')
    },
    sidebarToggle (e) {
      e.preventDefault()
      document.body.classList.toggle('sidebar-hidden')
    },
    sidebarMinimize (e) {
      e.preventDefault()
      document.body.classList.toggle('sidebar-minimized')
    },
    mobileSidebarToggle (e) {
      e.preventDefault()
      document.body.classList.toggle('sidebar-mobile-show')
    },
    asideToggle (e) {
      e.preventDefault()
      document.body.classList.toggle('aside-menu-hidden')
    },
    getUser () {
      return sessionStorage.getItem('user')
    },
    getAccountBalance () {
      // TODO
      var data = new FormData()
      data.append('function', 'accountBalance')
      data.append('email', sessionStorage.getItem('email'))
      data.append('company', sessionStorage.getItem('company'))
      excecute(data).then((res) => {
        // TODO
        this.balance = res.data.data
      }).catch((e) => {
        // TODO
      })
    }
  },
  computed: {
  },
  created () {
    this.getAccountBalance()
  }
}
</script>

<style scoped>
.navbar-brand {
  background-size: 55px auto !important;
}
</style>
