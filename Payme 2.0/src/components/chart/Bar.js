// import 'chart.js'
import {Bar,mixins} from 'vue-chartjs'
const { reactiveProp } = mixins

export default ({
    extends: Bar,
    mixins: [reactiveProp],
    props: ['options'],
    mounted () {
        this.renderChart({
            labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            datasets: [
                {
                    label: 'Sales',
                    backgroundColor: '#f87979',
                    data: [40, 20, 12, 39, 10, 40, 39, 80, 40, 20, 12, 11]
                }
            ]
        }, {responsive: true, maintainAspectRatio: false})
    }
})
