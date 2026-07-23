pipeline {
    agent any

    options {
        disableConcurrentBuilds()
        timestamps()
        timeout(time: 20, unit: 'MINUTES')
    }

    stages {
        stage('Validar conexão') {
            steps {
                sshagent(credentials: ['0b580ebd-afc7-42cc-98fa-a17885f5633e']) {
                    sh '''
                        ssh \
                          -o BatchMode=yes \
                          -o StrictHostKeyChecking=accept-new \
                          -o ConnectTimeout=10 \
                          deploy@179.197.237.14 \
                          "echo 'Conexão SSH com o servidor realizada com sucesso'"
                    '''
                }
            }
        }

        stage('Deploy produção') {
            steps {
                sshagent(credentials: ['0b580ebd-afc7-42cc-98fa-a17885f5633e']) {
                    sh '''
                        ssh \
                          -o BatchMode=yes \
                          -o StrictHostKeyChecking=accept-new \
                          deploy@179.197.237.14 \
                          "cd /opt/apps/fabrica-loja && ./deploy.sh"
                    '''
                }
            }
        }

        stage('Verificar aplicação') {
            steps {
                sshagent(credentials: ['0b580ebd-afc7-42cc-98fa-a17885f5633e']) {
                    sh '''
                        ssh \
                          -o BatchMode=yes \
                          -o StrictHostKeyChecking=accept-new \
                          deploy@179.197.237.14 \
                          "cd /opt/apps/fabrica-loja && docker compose --env-file .env.production -f compose.production.yml ps"
                    '''
                }
            }
        }
    }

    post {
        success {
            echo 'Deploy realizado com sucesso.'
        }

        failure {
            echo 'O deploy falhou. Consulte o Console Output para encontrar o erro.'
        }

        always {
            cleanWs()
        }
    }
}
