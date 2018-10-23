// import 'chart.js'
import {Doughnut ,mixins} from 'vue-chartjs'
const { reactiveProp } = mixins

export default ({
    extends: Doughnut,
    mixins: [reactiveProp],
    props: ['options'],
    mounted () {
        this.renderChart({
            labels: ['MPESA', 'CARDS', 'BANK', 'CASH'],
            datasets: [
                {
                    backgroundColor: [
                        '#41B883',
                        '#E46651',
                        '#00D8FF',
                        '#DD1B16'
                    ],
                    data: [40, 20, 80, 10]
                }
            ]
        }, {responsive: true, maintainAspectRatio: false})
    }
})
