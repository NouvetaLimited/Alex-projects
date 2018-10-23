<template>
  <v-container fluid>
    <v-flex xs12>
      <v-card class="grey lighten-4 elevation-0">

          <v-card-title>
              {{title}}
              <v-spacer></v-spacer>
              <!--<v-text-field append-icon="search" label="Search" single-line hide-details v-model="search"></v-text-field>-->
              <v-btn fab small class="grey" @click.native="cancel()">
                  <v-icon>cancel</v-icon>
              </v-btn>
              &nbsp;
              <v-btn fab small class="purple" @click.native="save()">
                  <v-icon>save</v-icon>
              </v-btn>
          </v-card-title>

        <v-card-text>
          <v-container fluid grid-list-md>
                <v-layout row wrap>
                  <v-flex xs6>
                    <v-subheader>Brand</v-subheader>
                  </v-flex>
                  <v-flex xs6>
                    <v-select
                            :items="brands"
                            v-model="e1Brand"
                            label="Select"

                    >
                    </v-select>

                  </v-flex>

                </v-layout>

              <v-layout row wrap>
                  <v-flex xs6>
                    <v-subheader>Category</v-subheader>
                  </v-flex>
                  <v-flex xs6>
                    <v-select
                            :items="categories"
                            v-model="e1Category"
                            label="Select"

                    >
                    </v-select>

                  </v-flex>

                </v-layout>

              <v-layout row wrap>
                  <v-flex xs6>
                    <v-subheader>Sub Category</v-subheader>
                  </v-flex>
                  <v-flex xs6>
                    <v-select
                            :items="subCategories"
                            v-model="e1SubCategory"
                            label="Select"

                    >
                    </v-select>

                  </v-flex>

                </v-layout>


              <v-layout row wrap>
                  <v-flex xs6>
                    <v-subheader>Product Name</v-subheader>
                  </v-flex>
                  <v-flex xs6>
                      <v-text-field
                              v-model="productName"
                              :counter="10"
                              label="Product Name"
                              required
                      ></v-text-field>

                  </v-flex>

                </v-layout>

              <v-layout row wrap>
                  <v-flex xs6>
                    <v-subheader>Description</v-subheader>
                  </v-flex>
                  <v-flex xs6>
                      <v-text-field
                              v-model="description"
                              :counter="1000"
                              label="Description"
                              required
                      ></v-text-field>

                  </v-flex>

                </v-layout>


              <v-layout row wrap>
                  <v-flex xs6>
                    <v-subheader>Price</v-subheader>
                  </v-flex>
                  <v-flex xs6>
                      <v-text-field
                              v-model="price"
                              :counter="1000"
                              label="Price"
                              required
                      ></v-text-field>

                  </v-flex>

                </v-layout>


              <v-layout row wrap>
                  <v-flex xs6>
                    <v-subheader>Image</v-subheader>
                  </v-flex>
                  <v-flex xs6>

                      <input  ref="fileToUpload" type="file" class="form-control" @change="selectFile">

                  </v-flex>

                </v-layout>


          </v-container>
        </v-card-text>
      </v-card>
    </v-flex>
  </v-container>
</template>
<script>
    import { execute } from '../api'
    import {Doughnut} from 'vue-chartjs'
    export default {
        name: 'AppProducts',
        data () {
            return {
                fileToUpload:null,
                brands:[],
                e1Brand:'' ,
                categories:[],
                e1Category:'' ,
                subCategories:[],
                e1SubCategory:'',
                productName:'',
                description:'',
                price:''
            }
        },
        methods: {
            selectFile (e) {
                // TODO
                this.fileToUpload = e.target.files[0]
            }, cancel () {

                this.$router.push('AppProducts')
            }, save() {
                const data = new FormData()
                data.append('TransactionType', 'AddProduct')
                data.append('brand_code', this.e1Brand)
                data.append('category_code', this.e1Category)
                data.append('sub_category_code', this.e1SubCategory)
                data.append('product_name', this.productName)
                data.append('description', this.description)
                data.append('price', this.price)
                data.append('fileToUpload', this.fileToUpload)

                execute(data).then((res) => {
                    alert( res.data.message);
                }).catch((e) => {
                    // TODO
                })
            },
            getBrandName () {
                const data = new FormData()
                data.append('TransactionType', 'getBrandsNames')
                data.append('keyword', '')

                execute(data).then((res) => {
                    this.brands = res.data.data
                }).catch((e) => {
                    // TODO
                })
            },getCategoryName () {
                const data = new FormData()
                data.append('TransactionType', 'getCategoryName')
                data.append('keyword', '')

                execute(data).then((res) => {
                    this.categories = res.data.data
                }).catch((e) => {
                    // TODO
                })
            },getSubCategoryName () {
                const data = new FormData()
                data.append('TransactionType', 'getSubCategoryName')
                data.append('keyword', '')

                execute(data).then((res) => {
                    this.subCategories = res.data.data
                }).catch((e) => {
                    // TODO
                })
            },
            getImageURL(image){
                return "https://deaconsapi.nouveta.co.ke/uploads/"+image;
            },
        },
        created () {
            this.getBrandName()
            this.getCategoryName()
            this.getSubCategoryName()
        }
    }
</script>
