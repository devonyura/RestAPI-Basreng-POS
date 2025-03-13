<?php

namespace App\Controllers;

use App\Models\SiswaModel;
use App\Models\ActivityLogModel;
use App\Helpers\JwtHelper;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Exception;


class SiswaController extends ResourceController
{
	protected $userID;
	protected $user;
	protected $modelName = 'App\Models\SiswaModel';
	protected $format    = 'json';

	// Contoh fungsi untuk menyimpan log aktivitas
	public function createLog($action, $details = null)
	{
		$decode_jwt = new JwtHelper();
		$logModel = new ActivityLogModel();

		$requset = service('request');
		$authHeader = $requset->getHeaderLine('Authorization');

		if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
			$token = $matches[1];

			// Decode token dan simpan dalam variabel global
			$decoded = $decode_jwt->validateJWT($token);

			if ($decoded) {
				$logModel->logActivity($decoded['id'], $decoded['username'], $action, $details);
			}
		}
	}

	// Ambil semua siswa
	public function index()
	{
		try {
			$data = $this->model->findAll();

			if (empty($data)) {
				$this->createLog('READ_ALL', 'Tidak ada data siswa.');
				return $this->failNotFound('Tidak ada data siswa.');
			}

			$this->createLog('READ_ALL', ['SUCCESS']);
			return $this->respond([
				'status' => 'success',
				'data' => $data
			]);
		} catch (\Exception $e) {

			$this->createLog('READ_ALL', ['ERROR']);
			return Services::response()
				->setJSON([
					'status' => 'error',
					'message' => 'Terjadi kesalahan pada server.',
					'error' => $e->getMessage()
				])
				->setStatusCode(500);
		}
	}

	// Tambah siswa
	public function create()
	{
		$rules = [
			'name' => 'required|min_length[3]|is_unique[siswa.name]',
			'address' => 'required|min_length[6]',
			'gender' => 'required'
		];

		if (!$this->validate($rules)) {
			return $this->failValidationErrors($this->validator->getErrors());
		}

		$model = new SiswaModel();
		$data = $this->request->getJSON();

		$siswaData = [
			'name' => $data->name,
			'address' => $data->address,
			'gender' => $data->gender
		];

		try {
			if (!$model->insert($siswaData)) {
				$this->createLog("INSERTS", ['ERROR']);
				return Services::response()
					->setJSON([
						'status' => 'error',
						'message' => 'Gagal menambahkan siswa.',
						'errors' => $model->errors() // Mengambil error dari model jika ada
					])
					->setStatusCode(500);
			}

			$this->createLog("INSERTS", ['SUCCESS']);
			return Services::response()
				->setJSON([
					'status' => 'success',
					'message' => 'Data siswa berhasil ditambahkan',
					'data' => $siswaData
				])
				->setStatusCode(201);
		} catch (\Exception $e) {
			$this->createLog('INSERTS', ['ERROR']);
			return Services::response()
				->setJSON([
					'status' => 'error',
					'message' => 'Terjadi kesalahan pada server.',
					'error' => $e->getMessage()
				])
				->setStatusCode(500);
		}
	}

	// Ambil satu siswa
	public function show($id = null)
	{
		try {
			$model = new SiswaModel();
			$data = $model->find($id);

			if (!$data) {
				$this->createLog("SHOW", ['ERROR: Siswa tidak ditemukan.']);
				return $this->failNotFound('Siswa tidak ditemukan.');
			}

			$this->createLog("SHOW", ['SUCCESS']);
			return Services::response()
				->setJSON([
					'status' => 'success',
					'data' => $data
				])
				->setStatusCode(200);
		} catch (\Exception $e) {

			$this->createLog('SHOW', ['ERROR']);
			return Services::response()
				->setJSON([
					'status' => 'error',
					'message' => 'Terjadi kesalahan pada server.',
					'error' => $e->getMessage()
				])
				->setStatusCode(500);
		}
	}

	// Update siswa
	public function update($id = null)
	{
		$rules = [
			'name' => 'required|min_length[3]',
			'address' => 'required|min_length[6]',
			'gender' => 'required'
		];

		$model = new SiswaModel();
		$data = $this->request->getJSON();

		if (!$model->find($id)) {
			return Services::response()
				->setJSON([
					'status' => 'error',
					'message' => 'Siswa tidak ditemukan'
				])
				->setStatusCode(404);
		}

		if (!$this->validate($rules)) {
			$this->createLog("UPDATE", ['ERROR: Siswa tidak ditambah.']);
			return $this->failValidationErrors($this->validator->getErrors());
		}

		$siswaData = [
			'name' => $data->name,
			'address' => $data->address,
			'gender' => $data->gender
		];

		try {

			$model->update($id, $siswaData);
			$this->createLog('UPDATE', ['SUCCESS']);
			return Services::response()
				->setJSON([
					'status' => 'success',
					'message' => 'Siswa berhasil diperbarui',
					'data' => $siswaData
				])
				->setStatusCode(200);
		} catch (Exception $e) {

			$this->createLog('UPDATE', ['ERROR']);
			return Services::response()
				->setJSON([
					'status' => 'error',
					'message' => 'Gagal memperbarui data siswa',
					'error' => $e->getMessage()
				])
				->setStatusCode(500);
		}
	}

	// Hapus siswa
	public function delete($id = null)
	{
		try {
			$model = new SiswaModel();

			if (!$model->find($id)) {
				$this->createLog("DELETE", ['ERROR: Siswa tidak ditemukan.']);
				return $this->failNotFound('Siswa tidak ditemukan.');
			}

			if (!$model->delete($id)) {

				$this->createLog('DLETE', ['ERROR']);
				return Services::response()
					->setJSON([
						'status' => 'error',
						'message' => 'Gagal menghapus siswa.'
					])
					->setStatusCode(500);
			}

			$this->createLog('DELETE', ['SUCCESS']);
			return Services::response()
				->setJSON([
					'status' => 'success',
					'message' => 'Siswa berhasil dihapus.'
				])
				->setStatusCode(200);
		} catch (\Exception $e) {

			$this->createLog('DLETE', ['ERROR']);
			return Services::response()
				->setJSON([
					'status' => 'error',
					'message' => 'Terjadi kesalahan pada server.',
					'error' => $e->getMessage()
				])
				->setStatusCode(500);
		}
	}
}
