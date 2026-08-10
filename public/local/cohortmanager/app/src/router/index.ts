import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import CohortList from '@/pages/CohortList.vue';
import CohortDetail from '@/pages/CohortDetail.vue';
import CohortEdit from '@/pages/CohortEdit.vue';
import CohortCreate from '@/pages/CohortCreate.vue';
import CohortCustomFields from '@/pages/CohortCustomFields.vue';
import RolesList from '@/pages/RolesList.vue';

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'CohortList',
    component: CohortList,
    meta: {
      title: 'Cohort Manager'
    }
  },
  {
    path: '/cohort/:id',
    name: 'CohortDetail',
    component: CohortDetail,
    meta: {
      title: 'Cohort Details'
    },
    props: true
  },
  {
    path: '/cohort/:id/edit',
    name: 'CohortEdit',
    component: CohortEdit,
    meta: {
      title: 'Edit Cohort'
    },
    props: true
  },
  {
    path: '/cohort/create',
    name: 'CohortCreate',
    component: CohortCreate,
    meta: {
      title: 'Create Cohort'
    }
  },
  {
    path: '/custom-fields',
    name: 'CohortCustomFields',
    component: CohortCustomFields,
    meta: {
      title: 'Custom Fields Management'
    }
  },
  {
    path: '/roles',
    name: 'RolesList',
    component: RolesList,
    meta: {
      title: 'Roles Management'
    }
  }
];

const router = createRouter({
  history: createWebHistory('/local/cohortmanager/'),
  routes
});

// Route guard for navigation
router.beforeEach((to, _from, next) => {
  // Set page title
  document.title = to.meta.title ? `${to.meta.title} - Cohort Manager` : 'Cohort Manager';
  next();
});

export default router;