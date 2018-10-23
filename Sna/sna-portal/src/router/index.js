import Vue from 'vue'
import Router from 'vue-router'

// Containers
import Full from '@/containers/Full'

// Views
import Dashboard from '@/views/Dashboard'
import Sms from '@/views/Sms'
import Airtime from '@/views/Airtime'
import Users from '@/views/Users'
import Login from '@/views/Login'
import Register from '@/views/Register'
import ForgotPassword from '@/views/ForgotPassword'
import Billing from '@/views/Billing'
import Contacts from '@/views/Contacts'
import AddUser from '@/views/AddUser'

Vue.use(Router)

export default new Router({
  mode: 'hash',
  linkActiveClass: 'open active',
  scrollBehavior: () => ({ y: 0 }),
  routes: [
    {
      path: '/forgot-password',
      name: 'ForgotPassword',
      component: ForgotPassword
    },
    {
      path: '/login',
      name: 'Login',
      component: Login
    },
    {
      path: '/register',
      name: 'Register',
      component: Register
    },
    {
      path: '',
      redirect: '/login'
    },
    {
      path: '',
      name: 'Home',
      component: Full,
      children: [
        {
          path: 'dashboard',
          name: 'Dashboard',
          component: Dashboard
        }

      ]
    },
    {
      path: '',
      name: 'Home',
      component: Full,
      children: [
        {
          path: '/sms',
          name: 'SMS',
          component: Sms
        }
      ]
    },
    {
      path: '',
      name: 'Home',
      component: Full,
      children: [
        {
          path: '/billing',
          name: 'Billing',
          component: Billing
        }
      ]
    },
    {
      path: '',
      name: 'Home',
      component: Full,
      children: [
        {
          path: '/airtime',
          name: 'Airtime',
          component: Airtime
        }
      ]
    },
    {
      path: '',
      name: 'Home',
      component: Full,
      children: [
        {
          path: '/users',
          name: 'Users',
          component: Users
        }
      ]
    },
    {
      path: '',
      name: 'Home',
      component: Full,
      children: [
        {
          path: '/contacts',
          name: 'Contacts',
          component: Contacts
        }
      ]
    },
    {
      path: '',
      name: 'Home',
      component: Full,
      children: [
        {
          path: '/adduser',
          name: 'AddUsers',
          component: AddUser
        }
      ]
    }
  ]
})
