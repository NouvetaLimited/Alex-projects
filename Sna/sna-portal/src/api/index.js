/**
 * Created by AlexBoey on 8/27/2017.
 */
import axios from 'axios'
// const API_URL = 'http://localhost:8080/nouveta/sna/index.php'
// const API_URL = 'http://sna.node.nouveta.tech/api/index.php'
const API_URL = 'https://sna.nouveta.co.ke/api/index.php'

export const upload = params => {
  const config = {
    headers: {
      'content-type': 'multipart/form-data'
    }
  }
  params.append('function', 'upload')
  return axios.post(API_URL, params, config)
}
export const excecute = params => {
  return axios.post(API_URL, params)
}
