import { useEffect, useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { useNavigate } from "react-router-dom";
import apiClient from "../api/client";
import ErrorMessage from "../components/ErrorMessage";

const initialForm = {
  full_name: "",
  date_of_birth: "",
  gender: "",
  phone: "",
  zalo_phone: "",
  city: "",
  district: "",
  street: "",
  house_number: "",
  paper_book_code: "",
  primary_doctor_id: "",
  primary_doctor_assigned_at: "",
  primary_doctor_note: "",
  patient_source_id: "",
  patient_source_note: "",
  emergency_contact_name: "",
  emergency_contact_phone: "",
  notes: "",
};

export default function PatientCreatePage() {
  const navigate = useNavigate();
  const [form, setForm] = useState(initialForm);
  const [validationErrors, setValidationErrors] = useState({});

  const doctorsQuery = useQuery({
    queryKey: ["doctors"],
    queryFn: async () => {
      const response = await apiClient.get("/doctors");
      return response.data;
    },
  });

  const sourcesQuery = useQuery({
    queryKey: ["patient-sources"],
    queryFn: async () => {
      const response = await apiClient.get("/patient-sources");
      return response.data;
    },
  });

  const createPatientMutation = useMutation({
    mutationFn: async (payload) => {
      const response = await apiClient.post("/patients", payload);
      return response.data;
    },
    onSuccess: (patient) => {
      navigate(`/patients/${patient.id}`);
    },
    onError: (error) => {
      setValidationErrors(error.validationErrors || {});
    },
  });

  useEffect(() => {
    setValidationErrors({});
  }, [form]);

  function updateField(event) {
    const { name, value } = event.target;

    setForm((current) => ({
      ...current,
      [name]: value,
    }));
  }

  function normalizePayload() {
    const payload = {};

    Object.entries(form).forEach(([key, value]) => {
      if (value !== "") {
        payload[key] = value;
      }
    });

    if (payload.primary_doctor_id) {
      payload.primary_doctor_id = Number(payload.primary_doctor_id);
    }

    if (payload.patient_source_id) {
      payload.patient_source_id = Number(payload.patient_source_id);
    }

    return payload;
  }

  function handleSubmit(event) {
    event.preventDefault();
    createPatientMutation.mutate(normalizePayload());
  }

  function fieldError(name) {
    return validationErrors[name]?.[0];
  }

  return (
    <section>
      <div className="page-header">
        <div>
          <h1>Thêm bệnh nhân</h1>
          <p>Tạo hồ sơ bệnh nhân mới.</p>
        </div>
      </div>

      <ErrorMessage error={createPatientMutation.error} />

      <form className="card form-grid" onSubmit={handleSubmit}>
        <div className="form-field form-field-wide">
          <label>Họ và tên *</label>
          <input
            name="full_name"
            value={form.full_name}
            onChange={updateField}
            required
          />
          {fieldError("full_name") && (
            <small className="field-error">{fieldError("full_name")}</small>
          )}
        </div>

        <div className="form-field">
          <label>Ngày sinh</label>
          <input
            type="date"
            name="date_of_birth"
            value={form.date_of_birth}
            onChange={updateField}
          />
        </div>

        <div className="form-field">
          <label>Giới tính</label>
          <select name="gender" value={form.gender} onChange={updateField}>
            <option value="">Chưa chọn</option>
            <option value="female">Nữ</option>
            <option value="male">Nam</option>
            <option value="other">Khác</option>
          </select>
        </div>

        <div className="form-field">
          <label>Số điện thoại</label>
          <input name="phone" value={form.phone} onChange={updateField} />
        </div>

        <div className="form-field">
          <label>Số liên hệ Zalo</label>
          <input
            name="zalo_phone"
            value={form.zalo_phone}
            onChange={updateField}
          />
        </div>

        <div className="form-field">
          <label>Thành phố</label>
          <input name="city" value={form.city} onChange={updateField} />
        </div>

        <div className="form-field">
          <label>Quận/huyện</label>
          <input
            name="district"
            value={form.district}
            onChange={updateField}
          />
        </div>

        <div className="form-field">
          <label>Tên đường</label>
          <input name="street" value={form.street} onChange={updateField} />
        </div>

        <div className="form-field">
          <label>Số nhà</label>
          <input
            name="house_number"
            value={form.house_number}
            onChange={updateField}
          />
        </div>

        <div className="form-field">
          <label>Mã sổ giấy</label>
          <input
            name="paper_book_code"
            value={form.paper_book_code}
            onChange={updateField}
          />
        </div>

        <div className="form-field">
          <label>Bác sĩ chính</label>
          <select
            name="primary_doctor_id"
            value={form.primary_doctor_id}
            onChange={updateField}
          >
            <option value="">Chưa chọn</option>
            {(doctorsQuery.data || []).map((doctor) => (
              <option key={doctor.id} value={doctor.id}>
                {doctor.title ? `${doctor.title} ` : ""}
                {doctor.full_name}
              </option>
            ))}
          </select>
        </div>

        <div className="form-field">
          <label>Ngày bắt đầu theo bác sĩ</label>
          <input
            type="date"
            name="primary_doctor_assigned_at"
            value={form.primary_doctor_assigned_at}
            onChange={updateField}
          />
        </div>

        <div className="form-field">
          <label>Nguồn bệnh nhân</label>
          <select
            name="patient_source_id"
            value={form.patient_source_id}
            onChange={updateField}
          >
            <option value="">Chưa chọn</option>
            {(sourcesQuery.data || []).map((source) => (
              <option key={source.id} value={source.id}>
                {source.name}
              </option>
            ))}
          </select>
        </div>

        <div className="form-field">
          <label>Tên liên hệ khẩn cấp</label>
          <input
            name="emergency_contact_name"
            value={form.emergency_contact_name}
            onChange={updateField}
          />
        </div>

        <div className="form-field">
          <label>SĐT liên hệ khẩn cấp</label>
          <input
            name="emergency_contact_phone"
            value={form.emergency_contact_phone}
            onChange={updateField}
          />
        </div>

        <div className="form-field form-field-wide">
          <label>Ghi chú bác sĩ chính</label>
          <textarea
            name="primary_doctor_note"
            value={form.primary_doctor_note}
            onChange={updateField}
          />
        </div>

        <div className="form-field form-field-wide">
          <label>Ghi chú nguồn bệnh nhân</label>
          <textarea
            name="patient_source_note"
            value={form.patient_source_note}
            onChange={updateField}
          />
        </div>

        <div className="form-field form-field-wide">
          <label>Ghi chú chung</label>
          <textarea name="notes" value={form.notes} onChange={updateField} />
        </div>

        <div className="form-actions form-field-wide">
          <button
            type="submit"
            className="button button-primary"
            disabled={createPatientMutation.isPending}
          >
            {createPatientMutation.isPending
              ? "Đang lưu..."
              : "Tạo bệnh nhân"}
          </button>

          <button
            type="button"
            className="button button-secondary"
            onClick={() => navigate("/patients")}
          >
            Hủy
          </button>
        </div>
      </form>
    </section>
  );
}
