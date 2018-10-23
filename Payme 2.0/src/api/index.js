
import axios from 'axios'
//const API_URL = 'https://zaka.nouveta.co.ke/api/index.php'
const API_URL = 'https://deaconsapi.nouveta.co.ke/index.php'
export const execute = params => {
  return axios.post(API_URL, params)
}
