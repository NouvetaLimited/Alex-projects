import Vue from 'vue'
import Router from 'vue-router'


import AppDashboard from '@/components/AppDashboard'
import AppOrders from '@/components/AppOrders'
import AppOrderItems from '@/components/AppOrderItems'
import AppCustomers from '@/components/AppCustomers'
import AppProducts from '@/components/AppProducts'
import AppProductForm from '@/components/AppProductForm'


Vue.use(Router)

export default new Router({
  routes: [
    {
      path: '/',
      name: 'AppDashboard',
      component: AppDashboard
    }, {
      path: '/AppDashboard',
      name: 'AppDashboard',
      component: AppDashboard
    },
    {
        path: '/AppOrders',
        name: 'AppOrders',
        component: AppOrders,
    },
      {
      path: '/AppCustomers',
      name: 'AppCustomers',
      component: AppCustomers,
    },
      {
      path: '/AppProducts',
      name: 'AppProducts',
      component: AppProducts,
    },
      {
      path: '/AppProductForm',
      name: 'AppProductForm',
      component: AppProductForm,
    },
      {
      path: '/AppOrderItems',
      name: 'AppOrderItems',
      component: AppOrderItems,
    }
  ],
    mode:'history'
})
