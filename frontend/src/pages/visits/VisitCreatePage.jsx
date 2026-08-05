import { useEffect, useMemo, useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { Link, useNavigate, useSearchParams } from "react-router-dom";

import apiClient from "../../api/client";
import ErrorMessage from "../../components/ErrorMessage";
import LoadingMessage from "../../components/LoadingMessage";

const initialForm = {
  patient_id: "",
  doctor_id: "",
  original_doctor_id: "",
  is_covering_doctor: false,
  visit_date: new Date().toISOString().slice(0, 10),
  visit_type: "follow_up",
  priority_flag: false,
  priority_type: "",
  priority_note: "",
  status: "registered",
  cashier_note: "",
};

export default function VisitCreatePage() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();

  const initialPatientId = searchParams.get("patient_id") || "";

  const [form, setForm] = useState({
    ...initialForm,
    patient_id: initialPatientId,
  });

  const [patientSearch, setPatientSearch] = useState("");
  const [validationErrors, setValidationErrors] = useState({});

  const doctorsQuery = useQuery({
    queryKey: ["doctors"],
    queryFn: async () => {
      const response = await apiClient.get("/doctors");
      return response.data;
    },
  });

  const patientsQuery = useQuery({
    queryKey: ["patients", patientSearch],
    queryFn: async () => {
      const response = await apiClient.get("/patients", {
        params: patientSearch ? { q: patientSearch } : {},
      });

      return response.data;
    },
  });

  const patientDetailQuery = useQuery({
    queryKey: ["patient", form.patient_id],
    queryFn: async () => {
      const response = await apiClient.get(`/patients/${form.patient_id}`);
      return response.data;
    },
    enabled: Boolean(form.patient_id),
  });

  const selectedPatient = patientDetailQuery.data;

  const createVisitMutation = useMutation({
    mutationFn: async (payload) => {
      const response = await apiClient.post("/visits", payload);
      return response.data;
    },
    onSuccess: (visit) => {
      navigate(`/visits/${visit.id}`);
    },
    onError: (error) => {
      setValidationErrors(error.validationErrors || {});
    },
  });

  useEffect(() => {
    if (!selectedPatient) {
      return;
    }

    const primaryDoctorId = selectedPatient.primary_doctor_id;

    setForm((current) => {
      if (
        current.doctor_id ||
        current.original_doctor_id ||
        !primaryDoctorId
      ) {
        return current;
      }

      return {
        ...current,
        doctor_id: String(primaryDoctorId),
        original_doctor_id: String(primaryDoctorId),
        is_covering_doctor: false,
      };
    });
  }, [selectedPatient]);

  const patients = patientsQuery.data?.data || [];
  const doctors = doctorsQuery.data || [];

  const selectedDoctor = useMemo(
    () =>
      doctors.find(
        (doctor) => String(doctor.id) === String(form.doctor_id)
      ),
    [doctors, form.doctor_id]
  );

  function updateField(event) {
    const { name, value, type, checked } = event.target;

    setValidationErrors({});

    setForm((current) => ({
      ...current,
      [name]: type === "checkbox" ? checked : value,
    }));
  }

  function handlePatientChange(event) {
    const patientId = event.target.value;

    setForm((current) => ({
      ...current,
      patient_id: patientId,
      doctor_id: "",
      original_doctor_id: "",
      is_covering_doctor: false,
    }));
  }

  function handleDoctorChange(event) {
    const doctorId = event.target.value;

    setForm((current) => {
      const originalDoctorId = current.original_doctor_id;

      return {
        ...current,
        doctor_id: doctorId,
        is_covering_doctor:
          Boolean(originalDoctorId) &&
          Boolean(doctorId) &&
          String(originalDoctorId) !== String(doctorId),
      };
    });
  }

  function normalizePayload() {
    const payload = {
      patient_id: Number(form.patient_id),
      doctor_id: Number(form.doctor_id),
      visit_date: form.visit_date,
      visit_type: form.visit_type,
      priority_flag: Boolean(form.priority_flag),
      status: form.status,
      is_covering_doctor: Boolean(form.is_covering_doctor),
    };

    if (form.original_doctor_id) {
      payload.original_doctor_id = Number(form.original_doctor_id);
    }

    if (form.priority_flag && form.priority_type) {
      payload.priority_type = form.priority_type;
    }

    if (form.priority_flag && form.priority_note.trim()) {
      payload.priority_note = form.priority_note.trim();
    }

    if (form.cashier_note.trim()) {
      payload.cashier_note = form.cashier_note.trim();
    }

    return payload;
  }

  function handleSubmit(event) {
    event.preventDefault();
    createVisitMutation.mutate(normalizePayload());
  }

  function fieldError(name) {
    return validationErrors[name]?.[0];
  }

  return (
    <section>
      <div className="page-header">
        <div>
          <h1>Tạo buổi khám</h1>
          <p>Mở hồ sơ buổi khám mới tại quầy tiếp nhận.</p>
        </div>

        <Link to="/visits" className="button button-secondary">
          Quay lại
        </Link>
      </div>

      <ErrorMessage error={createVisitMutation.error} />

      <form className="card form-grid" onSubmit={handleSubmit}>
        <div className="form-field form-field-wide">
          <label>Tìm bệnh nhân</label>

          <input
            value={patientSearch}
            onChange={(event) => setPatientSearch(event.target.value)}
            placeholder="Nhập tên, mã bệnh nhân, số điện thoại hoặc mã sổ..."
          />
        </div>

        <div className="form-field form-field-wide">
          <label>Bệnh nhân *</label>

          <select
            name="patient_id"
            value={form.patient_id}
            onChange={handlePatientChange}
            required
          >
            <option value="">Chọn bệnh nhân</option>

            {selectedPatient &&
              !patients.some(
                (patient) => String(patient.id) === String(selectedPatient.id)
              ) && (
                <option value={selectedPatient.id}>
                  {selectedPatient.patient_code} — {selectedPatient.full_name}
                </option>
              )}

            {patients.map((patient) => (
              <option key={patient.id} value={patient.id}>
                {patient.patient_code} — {patient.full_name}
                {patient.phone ? ` — ${patient.phone}` : ""}
              </option>
            ))}
          </select>

          {fieldError("patient_id") && (
            <small className="field-error">
              {fieldError("patient_id")}
            </small>
          )}
        </div>

        {patientDetailQuery.isLoading && form.patient_id && (
          <div className="form-field form-field-wide">
            <LoadingMessage text="Đang tải hồ sơ bệnh nhân..." />
          </div>
        )}

        {selectedPatient && (
          <div className="form-field form-field-wide patient-summary">
            <strong>{selectedPatient.full_name}</strong>

            <span>
              Mã bệnh nhân: {selectedPatient.patient_code}
            </span>

            <span>
              Bác sĩ chính:{" "}
              {selectedPatient.primary_doctor?.full_name || "Chưa có"}
            </span>

            <span>
              Ngày sinh: {selectedPatient.date_of_birth || "Chưa có"}
            </span>
          </div>
        )}

        <div className="form-field">
          <label>Ngày khám *</label>

          <input
            type="date"
            name="visit_date"
            value={form.visit_date}
            onChange={updateField}
            required
          />

          {fieldError("visit_date") && (
            <small className="field-error">
              {fieldError("visit_date")}
            </small>
          )}
        </div>

        <div className="form-field">
          <label>Loại khám *</label>

          <select
            name="visit_type"
            value={form.visit_type}
            onChange={updateField}
            required
          >
            <option value="follow_up">Tái khám</option>
            <option value="first_visit">Khám lần đầu</option>
          </select>

          {fieldError("visit_type") && (
            <small className="field-error">
              {fieldError("visit_type")}
            </small>
          )}
        </div>

        <div className="form-field">
          <label>Bác sĩ khám thực tế *</label>

          <select
            name="doctor_id"
            value={form.doctor_id}
            onChange={handleDoctorChange}
            required
          >
            <option value="">Chọn bác sĩ</option>

            {doctors.map((doctor) => (
              <option key={doctor.id} value={doctor.id}>
                {doctor.title ? `${doctor.title} ` : ""}
                {doctor.full_name}
              </option>
            ))}
          </select>

          {fieldError("doctor_id") && (
            <small className="field-error">
              {fieldError("doctor_id")}
            </small>
          )}
        </div>

        <div className="form-field">
          <label>Bác sĩ dự kiến / bác sĩ chính</label>

          <select
            name="original_doctor_id"
            value={form.original_doctor_id}
            onChange={updateField}
          >
            <option value="">Chưa xác định</option>

            {doctors.map((doctor) => (
              <option key={doctor.id} value={doctor.id}>
                {doctor.title ? `${doctor.title} ` : ""}
                {doctor.full_name}
              </option>
            ))}
          </select>
        </div>

        {form.is_covering_doctor && (
          <div className="form-field form-field-wide warning-box">
            Bác sĩ khám hôm nay khác với bác sĩ chính của bệnh nhân.
            Đây sẽ được lưu là một buổi khám thay.
          </div>
        )}

        <div className="form-field form-field-wide">
          <label className="checkbox-label">
            <input
              type="checkbox"
              name="priority_flag"
              checked={form.priority_flag}
              onChange={updateField}
            />

            <span>Đánh dấu bệnh nhân ưu tiên</span>
          </label>
        </div>

        {form.priority_flag && (
          <>
            <div className="form-field">
              <label>Loại ưu tiên *</label>

              <select
                name="priority_type"
                value={form.priority_type}
                onChange={updateField}
                required
              >
                <option value="">Chọn loại ưu tiên</option>
                <option value="elderly">Người cao tuổi</option>
                <option value="weak">Bệnh nhân yếu</option>
                <option value="emergency">Cấp cứu</option>
                <option value="other">Khác</option>
              </select>

              {fieldError("priority_type") && (
                <small className="field-error">
                  {fieldError("priority_type")}
                </small>
              )}
            </div>

            <div className="form-field">
              <label>Ghi chú ưu tiên</label>

              <input
                name="priority_note"
                value={form.priority_note}
                onChange={updateField}
                placeholder="Ví dụ: bệnh nhân trên 70 tuổi..."
              />
            </div>
          </>
        )}

        <div className="form-field form-field-wide">
          <label>Ghi chú từ quầy</label>

          <textarea
            name="cashier_note"
            value={form.cashier_note}
            onChange={updateField}
            placeholder="Các xét nghiệm được đánh dấu, ghi chú giấy tờ..."
          />
        </div>

        <div className="form-field form-field-wide visit-preview">
          <h3>Thông tin dự kiến</h3>

          <p>
            <strong>Bác sĩ:</strong>{" "}
            {selectedDoctor?.full_name || "Chưa chọn"}
          </p>

          <p>
            <strong>Loại khám:</strong>{" "}
            {form.visit_type === "first_visit"
              ? "Khám lần đầu"
              : "Tái khám"}
          </p>

          <p>
            <strong>Ưu tiên:</strong>{" "}
            {form.priority_flag ? "Có" : "Không"}
          </p>

          <p>
            Số thứ tự sẽ được backend tự động tạo theo bác sĩ và ngày khám.
          </p>
        </div>

        <div className="form-actions form-field-wide">
          <button
            type="submit"
            className="button button-primary"
            disabled={
              createVisitMutation.isPending ||
              !form.patient_id ||
              !form.doctor_id
            }
          >
            {createVisitMutation.isPending
              ? "Đang tạo..."
              : "Tạo buổi khám"}
          </button>

          <Link to="/visits" className="button button-secondary">
            Hủy
          </Link>
        </div>
      </form>
    </section>
  );
}
