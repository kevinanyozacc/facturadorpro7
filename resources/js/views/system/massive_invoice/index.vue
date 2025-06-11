<template>
    <div>
        <header class="page-header bg-primary">
            <h2 class="text-white"><a href="/dashboard"><i class="fa fa-list-alt"></i></a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span class="text-white">Facturas Masivas</span></li>
            </ol>
        </header>
        
        <div class="card">
            <div class="card-header bg-teal">
                <div>
                    <button @click="showUploadModal = true" class="btn btn-primary mr-2 float-right">
                        <i class="fas fa-upload"></i> Subir Facturas
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive" v-loading="loading">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Emisión</th>
                                <th>Cliente</th>
                                <th>Número</th> 
                                <th>Estado</th>
                                <th>T.Gravado</th>
                                <th>T.IGV</th>
                                <th>Saldo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="record in records" :key="record.id">
                                <td>{{ formatDate(record.fecha_emision) }}</td>
                                <td>
                                    <p class="mb-0">CLIENTE GENERAL</p>
                                    <small>{{ record.ruc }}</small>
                                </td>
                                <td>
                                    <p class="mb-0">{{ record.serie_comprobante }}</p>
                                    <small>{{ getTipoDoc(record.tipo_comprobante) }}</small>
                                </td>
                                <td>
                                    <span :class="getStatusClass(record.status)">
                                        {{ record.estado_sunat || record.status }}
                                    </span>
                                </td>
                                <td>S/. {{ record.total_gravado }}</td>
                                <td>S/. {{ record.total_igv }}</td>
                                <td>S/. {{ record.total_venta }}</td>
                                <td>
                                    <button @click="downloadFile(record.id, 'pdf')" 
                                        class="btn btn-sm btn-info mr-1" 
                                        title="Descargar PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </button>
                                    <button @click="downloadFile(record.id, 'xml')" 
                                        class="btn btn-sm btn-secondary" 
                                        title="Descargar XML">
                                        <i class="fas fa-file-code"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de carga masiva -->
        <el-dialog
            title="Subir Facturas Masivas"
            :visible.sync="showUploadModal"
            width="500px"
            :close-on-click-modal="false"
            :before-close="closeModal">
            
            <div class="text-center">
                <button @click="downloadFormat" class="btn btn-info mb-4 w-100">
                    <i class="fas fa-download"></i> Descargar Formato Excel
                </button>
                
                <div class="upload-area p-4 border rounded" v-if="!fileSelected">
                    <input
                        type="file"
                        ref="fileInput"
                        @change="handleFileUpload"
                        accept=".xlsx,.xls"
                        class="d-none">
                    <button @click="$refs.fileInput.click()" class="btn btn-outline-primary w-100">
                        <i class="fas fa-file-excel"></i> Seleccionar Archivo Excel
                    </button>
                </div>

                <div v-if="fileSelected" class="mt-3">
                    <p class="mb-2">
                        <i class="fas fa-file-excel text-success"></i> 
                        {{ selectedFileName }}
                    </p>
                    <button @click="processFile" class="btn btn-success" :disabled="processing">
                        <i class="fas fa-cog fa-spin" v-if="processing"></i>
                        {{ processing ? 'Procesando facturas...' : 'Procesar Facturas' }}
                    </button>
                </div>

                <el-progress 
                    v-if="processing" 
                    :percentage="progressPercentage"
                    :format="format"
                    class="mt-3">
                </el-progress>
            </div>
        </el-dialog>
    </div>
</template>

<script>
export default {
    data() {
        return {
            resource: 'massive-invoice',
            loading: false,
            showUploadModal: false,
            fileSelected: false,
            selectedFileName: '',
            processing: false,
            selectedFile: null,
            records: [],
            progressPercentage: 0
        }
    },
    created() {
        this.getRecords()
    },
    methods: {
        async getRecords() {
            this.loading = true
            try {
                const response = await this.$http.get(`/${this.resource}/records`)
                if (response.data.success) {
                    this.records = response.data.data
                }
            } catch (error) {
                console.error(error)
                this.$message.error('Error al cargar registros')
            } finally {
                this.loading = false
            }
        },
        formatDate(date) {
            if (!date) return '';
            const d = new Date(date);
            return d.toLocaleDateString('es-PE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        },
        getTipoDoc(tipo) {
            const tipos = {
                '01': 'FACTURA ELECTRÓNICA',
                '03': 'BOLETA DE VENTA ELECTRÓNICA'
            };
            return tipos[tipo] || tipo;
        },
        getStatusClass(status) {
            return {
                'badge badge-success': ['Registrado', 'Aceptado', 'PROCESADO'].includes(status),
                'badge badge-danger': ['Rechazado', 'ERROR'].includes(status),
                'badge badge-warning': ['Pendiente', 'Enviado'].includes(status)
            }
        },
        downloadFormat() {
            window.open(`/${this.resource}/download-format`, '_blank')
        },
        handleFileUpload(event) {
            const file = event.target.files[0]
            if (file) {
                this.selectedFile = file
                this.selectedFileName = file.name
                this.fileSelected = true
            }
        },
        format(percentage) {
            return percentage === 100 ? 'Completado' : `${percentage}%`
        },
        async processFile() {
            if (!this.selectedFile) return
            
            this.processing = true
            const formData = new FormData()
            formData.append('file', this.selectedFile)

            try {
                // Primer paso: Validar y procesar Excel
                const uploadResponse = await this.$http.post(`/${this.resource}/upload`, formData)
                
                if (!uploadResponse.data.success) {
                    throw new Error(uploadResponse.data.message)
                }

                // Segundo paso: Procesar documentos
                this.progressPercentage = 50
                const processResponse = await this.$http.post(`/${this.resource}/process`, {
                    documents: uploadResponse.data.data
                })

                if (processResponse.data.success) {
                    this.progressPercentage = 100
                    await this.$message({
                        message: 'Facturas procesadas correctamente',
                        type: 'success'
                    })
                    this.closeModal()
                    this.getRecords()
                } else {
                    throw new Error(processResponse.data.message)
                }
            } catch (error) {
                console.error('Error:', error)
                const errorMessage = error.response && error.response.data ? 
                    error.response.data.message : (error.message || 'Error al procesar el archivo')
                this.$message.error(errorMessage)
            } finally {
                this.processing = false
                this.progressPercentage = 0
            }
        },
        async downloadFile(id, type) {
            try {
                window.open(`/${this.resource}/download/${id}/${type}`, '_blank')
            } catch (error) {
                console.error(error)
                this.$message.error('Error al descargar archivo')
            }
        },
        closeModal() {
            this.showUploadModal = false
            this.fileSelected = false
            this.selectedFileName = ''
            this.selectedFile = null
            this.processing = false
            this.progressPercentage = 0
        }
    }
}
</script>

<style scoped>
.bg-teal {
    background-color: #009688;
}
.badge {
    padding: 0.5em 1em;
    font-size: 0.85em;
}
.upload-area {
    border-style: dashed !important;
    cursor: pointer;
    min-height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.table td {
    vertical-align: middle !important;
}
.table small {
    color: #6c757d;
    font-size: 85%;
}
</style>
